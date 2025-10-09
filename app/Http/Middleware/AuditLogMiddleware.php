<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class AuditLogMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // in milliseconds

        // Log the request
        $this->logRequest($request, $response, $duration);

        return $response;
    }

    /**
     * Log the API request.
     */
    protected function logRequest($request, $response, float $duration): void
    {
        try {
            $user = Auth::user();
            $statusCode = $response->getStatusCode();
            
            // Determine log severity based on status code
            $severity = $this->determineSeverity($statusCode);
            
            // Skip logging for certain endpoints to avoid noise
            if ($this->shouldSkipLogging($request)) {
                return;
            }

            // Prepare request data (sanitize sensitive information)
            $requestData = $this->sanitizeRequestData($request->all());
            
            // Log the API request
            AuditLog::create([
                'user_id' => $user?->id,
                'action' => 'api_request',
                'service' => $this->extractServiceFromPath($request->path()),
                'description' => sprintf(
                    '%s %s - %d (%s ms)',
                    $request->method(),
                    $request->path(),
                    $statusCode,
                    $duration
                ),
                'old_values' => null,
                'new_values' => [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'query_params' => $request->query(),
                    'request_data' => $requestData,
                    'status_code' => $statusCode,
                    'duration_ms' => $duration,
                    'user_agent' => $request->userAgent(),
                    'referer' => $request->header('referer')
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'severity' => $severity,
                'created_at' => now()
            ]);

        } catch (\Exception $e) {
            // Log error but don't break the request flow
            Log::error('Audit Log Middleware Error: ' . $e->getMessage(), [
                'request_path' => $request->path(),
                'request_method' => $request->method(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Determine log severity based on HTTP status code.
     */
    protected function determineSeverity(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'critical';
        } elseif ($statusCode >= 400) {
            return 'warning';
        } elseif ($statusCode >= 300) {
            return 'info';
        } else {
            return 'info';
        }
    }

    /**
     * Check if logging should be skipped for this request.
     */
    protected function shouldSkipLogging(Request $request): bool
    {
        $skipPaths = [
            'api/health',
            'api/ping',
            'api/status',
        ];

        $path = $request->path();
        
        foreach ($skipPaths as $skipPath) {
            if (str_starts_with($path, $skipPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract service name from request path.
     */
    protected function extractServiceFromPath(string $path): string
    {
        $segments = explode('/', $path);
        
        // For paths like api/sahbandar/*, api/spb/*, etc.
        if (count($segments) >= 3 && $segments[0] === 'api') {
            return $segments[1];
        }
        
        return 'sso';
    }

    /**
     * Sanitize request data to remove sensitive information.
     */
    protected function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            'api_key',
            'secret',
            'private_key',
            'credit_card',
            'ssn',
            'social_security'
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        // Recursively sanitize nested arrays
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeRequestData($value);
            }
        }

        return $data;
    }
}