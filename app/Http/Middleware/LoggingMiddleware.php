<?php

namespace App\Http\Middleware;

use App\Services\LoggingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class LoggingMiddleware
{
    private LoggingService $loggingService;

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $startTime = microtime(true);

        // Skip logging for certain routes
        if ($this->shouldSkipLogging($request)) {
            return $next($request);
        }

        // Log request start
        $this->logRequestStart($request);

        $response = $next($request);

        // Calculate duration
        $duration = microtime(true) - $startTime;

        // Log request completion
        $this->loggingService->logApiRequest($request, $response, $duration);

        // Log performance metrics for slow requests
        if ($duration > 1.0) { // Log if request takes more than 1 second
            $this->loggingService->logPerformance(
                'slow_api_request',
                $duration,
                [
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'status' => $response->getStatusCode()
                ]
            );
        }

        // Log security events for suspicious activity
        $this->checkForSuspiciousActivity($request, $response);

        return $response;
    }

    /**
     * Log request start for debugging
     */
    private function logRequestStart(Request $request): void
    {
        if (config('app.debug')) {
            $this->loggingService->logApiRequest($request);
        }
    }

    /**
     * Check if logging should be skipped for this request
     */
    private function shouldSkipLogging(Request $request): bool
    {
        $skipRoutes = [
            'health',
            'metrics',
            'ping',
            '_debugbar'
        ];

        $path = $request->path();

        foreach ($skipRoutes as $skipRoute) {
            if (str_contains($path, $skipRoute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for suspicious activity and log security events
     */
    private function checkForSuspiciousActivity(Request $request, SymfonyResponse $response): void
    {
        // Log failed authentication attempts
        if ($response->getStatusCode() === 401) {
            $this->loggingService->logSecurityEvent(
                'unauthorized_access_attempt',
                [
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'user_agent' => $request->userAgent(),
                    'referer' => $request->header('referer')
                ],
                'warning'
            );
        }

        // Log potential SQL injection attempts
        if ($this->detectSQLInjectionAttempt($request)) {
            $this->loggingService->logSecurityEvent(
                'sql_injection_attempt',
                [
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'query_params' => $request->query(),
                    'post_data' => $this->sanitizePostData($request->all())
                ],
                'critical'
            );
        }

        // Log potential XSS attempts
        if ($this->detectXSSAttempt($request)) {
            $this->loggingService->logSecurityEvent(
                'xss_attempt',
                [
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'suspicious_input' => $this->findSuspiciousInput($request)
                ],
                'warning'
            );
        }

        // Log excessive request rates from single IP
        if ($this->detectExcessiveRequests($request)) {
            $this->loggingService->logSecurityEvent(
                'excessive_requests',
                [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'url' => $request->fullUrl()
                ],
                'warning'
            );
        }

        // Log access to sensitive endpoints
        if ($this->isSensitiveEndpoint($request)) {
            $this->loggingService->logSecurityEvent(
                'sensitive_endpoint_access',
                [
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'status' => $response->getStatusCode(),
                    'user_id' => auth()->id()
                ],
                'info'
            );
        }
    }

    /**
     * Detect potential SQL injection attempts
     */
    private function detectSQLInjectionAttempt(Request $request): bool
    {
        $suspiciousPatterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bEXEC\b|\bEXECUTE\b)/i',
            '/(\'|\")(\s*)(OR|AND)(\s*)(\'|\")/i',
            '/(\bOR\b.*=.*)/i',
            '/(\bAND\b.*=.*)/i'
        ];

        $allInput = array_merge(
            $request->query(),
            $request->all(),
            $request->headers->all()
        );

        foreach ($allInput as $key => $value) {
            if (is_string($value)) {
                foreach ($suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Detect potential XSS attempts
     */
    private function detectXSSAttempt(Request $request): bool
    {
        $suspiciousPatterns = [
            '/<script[^>]*>.*?<\/script>/i',
            '/<iframe[^>]*>.*?<\/iframe>/i',
            '/javascript:/i',
            '/on\w+\s*=/i', // onclick, onload, etc.
            '/<img[^>]*src[^>]*>/i',
            '/<object[^>]*>.*?<\/object>/i',
            '/<embed[^>]*>.*?<\/embed>/i'
        ];

        $allInput = array_merge(
            $request->query(),
            $request->all()
        );

        foreach ($allInput as $key => $value) {
            if (is_string($value)) {
                foreach ($suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Detect excessive requests from single IP
     */
    private function detectExcessiveRequests(Request $request): bool
    {
        $ip = $request->ip();
        $cacheKey = "request_count_{$ip}_" . now()->format('Y-m-d-H-i');
        
        $count = cache()->increment($cacheKey, 1);
        
        if ($count === 1) {
            cache()->put($cacheKey, 1, 60); // Expire after 1 minute
        }

        // More than 100 requests per minute from single IP is suspicious
        return $count > 100;
    }

    /**
     * Check if endpoint is sensitive
     */
    private function isSensitiveEndpoint(Request $request): bool
    {
        $sensitiveEndpoints = [
            'api/auth/login',
            'api/auth/logout',
            'api/auth/register',
            'api/user/profile',
            'api/users',
            'api/sso',
            'api/admin'
        ];

        $path = $request->path();

        foreach ($sensitiveEndpoints as $endpoint) {
            if (str_starts_with($path, $endpoint)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find suspicious input in request
     */
    private function findSuspiciousInput(Request $request): array
    {
        $suspicious = [];
        $allInput = array_merge($request->query(), $request->all());

        foreach ($allInput as $key => $value) {
            if (is_string($value) && (
                str_contains($value, '<script') ||
                str_contains($value, 'javascript:') ||
                str_contains($value, 'onclick=') ||
                str_contains($value, 'onload=')
            )) {
                $suspicious[$key] = substr($value, 0, 100); // Limit to first 100 chars
            }
        }

        return $suspicious;
    }

    /**
     * Sanitize POST data for logging (remove sensitive information)
     */
    private function sanitizePostData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            'api_key',
            'secret',
            'private_key'
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }
}