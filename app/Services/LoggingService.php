<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LoggingService
{
    /**
     * Log security events
     */
    public function logSecurityEvent(string $event, array $data = [], string $level = 'warning'): void
    {
        $logData = [
            'event' => $event,
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
            'data' => $data
        ];

        // Log to Laravel log
        Log::channel('security')->{$level}($event, $logData);

        // Create audit log entry for critical security events
        if (in_array($level, ['error', 'critical', 'alert', 'emergency'])) {
            $this->createAuditLog('security_event', null, [
                'event' => $event,
                'level' => $level,
                'data' => $data
            ]);
        }
    }

    /**
     * Log authentication events
     */
    public function logAuthEvent(string $action, $user = null, array $metadata = []): void
    {
        $userId = $user ? $user->id : Auth::id();
        
        $logData = [
            'action' => $action,
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
            'metadata' => $metadata
        ];

        Log::channel('auth')->info($action, $logData);

        // Create audit log
        $this->createAuditLog($action, $user, $metadata);
    }

    /**
     * Log API requests and responses
     */
    public function logApiRequest(Request $request, $response = null, float $duration = null): void
    {
        $logData = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_id' => Auth::id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_size' => strlen($request->getContent()),
            'response_status' => $response ? $response->getStatusCode() : null,
            'response_size' => $response ? strlen($response->getContent()) : null,
            'duration_ms' => $duration ? round($duration * 1000, 2) : null,
            'timestamp' => now()->toISOString()
        ];

        // Log based on response status
        if ($response && $response->getStatusCode() >= 400) {
            Log::channel('api')->warning('API Error Response', $logData);
        } else {
            Log::channel('api')->info('API Request', $logData);
        }
    }

    /**
     * Log performance metrics
     */
    public function logPerformance(string $operation, float $duration, array $metrics = []): void
    {
        $logData = [
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString(),
            'metrics' => $metrics
        ];

        // Log performance warnings for slow operations
        if ($duration > 1.0) { // More than 1 second
            Log::channel('performance')->warning('Slow Operation', $logData);
        } else {
            Log::channel('performance')->info('Performance Metric', $logData);
        }
    }

    /**
     * Log database queries
     */
    public function logDatabaseQuery(string $sql, array $bindings, float $time): void
    {
        $logData = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time_ms' => round($time, 2),
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString()
        ];

        // Log slow queries
        if ($time > 100) { // More than 100ms
            Log::channel('database')->warning('Slow Query', $logData);
        } else {
            Log::channel('database')->debug('Database Query', $logData);
        }
    }

    /**
     * Log SSO events
     */
    public function logSSOEvent(string $action, $session = null, array $metadata = []): void
    {
        $logData = [
            'action' => $action,
            'session_id' => $session ? $session->id : null,
            'service' => $session ? $session->service : ($metadata['service'] ?? null),
            'user_id' => $session ? $session->user_id : Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
            'metadata' => $metadata
        ];

        Log::channel('sso')->info($action, $logData);

        // Create audit log for SSO events
        $this->createAuditLog($action, $session, $metadata);
    }

    /**
     * Log microservice interactions
     */
    public function logMicroserviceCall(string $service, string $endpoint, array $request, $response, float $duration): void
    {
        $logData = [
            'service' => $service,
            'endpoint' => $endpoint,
            'request_data' => $request,
            'response_status' => $response['status'] ?? null,
            'response_data' => $response['data'] ?? null,
            'duration_ms' => round($duration * 1000, 2),
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString()
        ];

        if (isset($response['status']) && $response['status'] >= 400) {
            Log::channel('microservices')->error('Microservice Error', $logData);
        } else {
            Log::channel('microservices')->info('Microservice Call', $logData);
        }
    }

    /**
     * Log system errors
     */
    public function logError(\Throwable $exception, array $context = []): void
    {
        $logData = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'url' => request()->fullUrl(),
            'timestamp' => now()->toISOString(),
            'context' => $context
        ];

        Log::channel('errors')->error('System Error', $logData);

        // Create audit log for critical errors
        $this->createAuditLog('system_error', null, [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'context' => $context
        ]);
    }

    /**
     * Log rate limiting events
     */
    public function logRateLimit(string $key, int $attempts, int $maxAttempts): void
    {
        $logData = [
            'rate_limit_key' => $key,
            'attempts' => $attempts,
            'max_attempts' => $maxAttempts,
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'timestamp' => now()->toISOString()
        ];

        Log::channel('security')->warning('Rate Limit Exceeded', $logData);

        // Create audit log for rate limiting
        $this->createAuditLog('rate_limit_exceeded', null, [
            'key' => $key,
            'attempts' => $attempts,
            'max_attempts' => $maxAttempts
        ]);
    }

    /**
     * Log cache operations
     */
    public function logCacheOperation(string $operation, string $key, $value = null, bool $hit = null): void
    {
        $logData = [
            'operation' => $operation,
            'key' => $key,
            'hit' => $hit,
            'value_size' => $value ? strlen(serialize($value)) : null,
            'timestamp' => now()->toISOString()
        ];

        Log::channel('cache')->debug('Cache Operation', $logData);
    }

    /**
     * Create audit log entry
     */
    private function createAuditLog(string $action, $auditable = null, array $changes = []): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'auditable_type' => $auditable ? get_class($auditable) : null,
                'auditable_id' => $auditable ? $auditable->id : null,
                'changes' => json_encode($changes),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        } catch (\Exception $e) {
            // Fallback logging if audit log creation fails
            Log::error('Failed to create audit log', [
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get log statistics
     */
    public function getLogStatistics(string $period = '24h'): array
    {
        $since = match($period) {
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subDay()
        };

        return [
            'period' => $period,
            'since' => $since->toISOString(),
            'audit_logs' => AuditLog::where('created_at', '>=', $since)->count(),
            'security_events' => AuditLog::where('created_at', '>=', $since)
                ->where('action', 'like', 'security_%')
                ->count(),
            'auth_events' => AuditLog::where('created_at', '>=', $since)
                ->whereIn('action', ['login', 'logout', 'login_failed', 'password_changed'])
                ->count(),
            'sso_events' => AuditLog::where('created_at', '>=', $since)
                ->where('action', 'like', 'sso_%')
                ->count(),
            'error_events' => AuditLog::where('created_at', '>=', $since)
                ->where('action', 'system_error')
                ->count()
        ];
    }

    /**
     * Search logs
     */
    public function searchLogs(array $criteria, int $limit = 100): array
    {
        $query = AuditLog::query();

        if (isset($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }

        if (isset($criteria['action'])) {
            $query->where('action', 'like', '%' . $criteria['action'] . '%');
        }

        if (isset($criteria['ip_address'])) {
            $query->where('ip_address', $criteria['ip_address']);
        }

        if (isset($criteria['date_from'])) {
            $query->where('created_at', '>=', $criteria['date_from']);
        }

        if (isset($criteria['date_to'])) {
            $query->where('created_at', '<=', $criteria['date_to']);
        }

        return $query->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Export logs
     */
    public function exportLogs(array $criteria, string $format = 'json'): string
    {
        $logs = $this->searchLogs($criteria, 10000); // Max 10k records for export

        return match($format) {
            'csv' => $this->exportToCsv($logs),
            'json' => json_encode($logs, JSON_PRETTY_PRINT),
            default => json_encode($logs, JSON_PRETTY_PRINT)
        };
    }

    /**
     * Convert logs to CSV format
     */
    private function exportToCsv(array $logs): string
    {
        if (empty($logs)) {
            return '';
        }

        $csv = "ID,User ID,Action,Auditable Type,Auditable ID,IP Address,User Agent,Created At,Changes\n";
        
        foreach ($logs as $log) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $log['id'],
                $log['user_id'] ?? '',
                $log['action'],
                $log['auditable_type'] ?? '',
                $log['auditable_id'] ?? '',
                $log['ip_address'],
                str_replace('"', '""', $log['user_agent']),
                $log['created_at'],
                str_replace('"', '""', $log['changes'])
            );
        }

        return $csv;
    }
}