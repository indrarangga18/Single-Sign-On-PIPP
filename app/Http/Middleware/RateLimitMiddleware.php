<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): mixed
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = (int) $maxAttempts;
        $decayMinutes = (int) $decayMinutes;

        if ($this->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
            // Log rate limit exceeded
            $user = Auth::user();
            AuditLog::logSecurityEvent(
                $user,
                'rate_limit_exceeded',
                $request->ip(),
                $request->userAgent(),
                [
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'max_attempts' => $maxAttempts,
                    'decay_minutes' => $decayMinutes,
                    'current_attempts' => $this->attempts($key)
                ]
            );

            return $this->buildResponse($key, $maxAttempts, $decayMinutes);
        }

        $this->hit($key, $decayMinutes);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts),
            $this->availableAt($key, $decayMinutes)
        );
    }

    /**
     * Resolve request signature.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $user = Auth::user();
        
        if ($user) {
            return 'rate_limit:user:' . $user->id . ':' . $request->ip();
        }

        return 'rate_limit:ip:' . $request->ip();
    }

    /**
     * Determine if the given key has been "accessed" too many times.
     */
    protected function tooManyAttempts(string $key, int $maxAttempts, int $decayMinutes): bool
    {
        return $this->attempts($key) >= $maxAttempts;
    }

    /**
     * Get the number of attempts for the given key.
     */
    protected function attempts(string $key): int
    {
        return Cache::get($key, 0);
    }

    /**
     * Increment the counter for a given key for a given decay time.
     */
    protected function hit(string $key, int $decayMinutes): int
    {
        $attempts = Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, now()->addMinutes($decayMinutes));
        
        return $attempts;
    }

    /**
     * Calculate the number of remaining attempts.
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - $this->attempts($key));
    }

    /**
     * Get the number of seconds until the "key" is accessible again.
     */
    protected function availableAt(string $key, int $decayMinutes): int
    {
        $cacheKey = $key . ':timer';
        $availableAt = Cache::get($cacheKey);
        
        if (!$availableAt) {
            $availableAt = now()->addMinutes($decayMinutes)->timestamp;
            Cache::put($cacheKey, $availableAt, now()->addMinutes($decayMinutes));
        }
        
        return max(0, $availableAt - now()->timestamp);
    }

    /**
     * Create a 'too many attempts' response.
     */
    protected function buildResponse(string $key, int $maxAttempts, int $decayMinutes): JsonResponse
    {
        $retryAfter = $this->availableAt($key, $decayMinutes);

        return response()->json([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $retryAfter
        ], 429)->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
            'Retry-After' => $retryAfter,
        ]);
    }

    /**
     * Add the limit header information to the given response.
     */
    protected function addHeaders($response, int $maxAttempts, int $remainingAttempts, int $retryAfter)
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
        ]);

        return $response;
    }
}