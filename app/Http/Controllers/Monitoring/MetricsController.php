<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Services\MonitoringService;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MetricsController extends Controller
{
    protected MonitoringService $monitoringService;
    protected LoggingService $loggingService;

    public function __construct(MonitoringService $monitoringService, LoggingService $loggingService)
    {
        $this->monitoringService = $monitoringService;
        $this->loggingService = $loggingService;
    }

    /**
     * General system metrics overview
     */
    public function general(Request $request): JsonResponse
    {
        $period = $request->query('period', '1h'); // 1h, 24h, 7d, 30d
        
        $metrics = [
            'timestamp' => now()->toISOString(),
            'period' => $period,
            'system' => [
                'uptime' => $this->getSystemUptime(),
                'memory_usage' => $this->getMemoryUsage(),
                'disk_usage' => $this->getDiskUsage()
            ],
            'api' => $this->getApiMetricsSummary($period),
            'database' => $this->getDatabaseMetricsSummary($period),
            'cache' => $this->getCacheMetricsSummary($period),
            'authentication' => $this->getAuthMetricsSummary($period),
            'sso' => $this->getSSOMetricsSummary($period),
            'security' => $this->getSecurityMetricsSummary($period)
        ];

        return response()->json($metrics);
    }

    /**
     * API-specific metrics
     */
    public function apiMetrics(Request $request): JsonResponse
    {
        $period = $request->query('period', '1h');
        $performanceMetrics = $this->monitoringService->getPerformanceMetrics();
        
        $metrics = [
            'timestamp' => now()->toISOString(),
            'period' => $period,
            'requests' => [
                'total' => Cache::get('metrics:api:total_requests', 0),
                'successful' => Cache::get('metrics:api:successful_requests', 0),
                'failed' => Cache::get('metrics:api:failed_requests', 0),
                'rate_limited' => Cache::get('metrics:api:rate_limited_requests', 0)
            ],
            'response_times' => [
                'average' => Cache::get('metrics:api:avg_response_time', 0),
                'p50' => Cache::get('metrics:api:p50_response_time', 0),
                'p95' => Cache::get('metrics:api:p95_response_time', 0),
                'p99' => Cache::get('metrics:api:p99_response_time', 0)
            ],
            'endpoints' => $this->getTopEndpoints($period),
            'errors' => $this->getApiErrors($period),
            'throughput' => Cache::get('metrics:api:throughput', 0)
        ];

        return response()->json($metrics);
    }

    /**
     * Database-specific metrics
     */
    public function databaseMetrics(Request $request): JsonResponse
    {
        $period = $request->query('period', '1h');
        $performanceMetrics = $this->monitoringService->getPerformanceMetrics();
        
        $metrics = [
            'timestamp' => now()->toISOString(),
            'period' => $period,
            'queries' => [
                'total' => Cache::get('metrics:db:total_queries', 0),
                'slow_queries' => Cache::get('metrics:db:slow_queries', 0),
                'failed_queries' => Cache::get('metrics:db:failed_queries', 0)
            ],
            'performance' => [
                'average_query_time' => Cache::get('metrics:db:avg_query_time', 0),
                'slowest_query_time' => Cache::get('metrics:db:slowest_query_time', 0),
                'connection_pool_usage' => Cache::get('metrics:db:connection_pool_usage', 0)
            ],
            'connections' => [
                'active' => Cache::get('metrics:db:active_connections', 0),
                'max_connections' => Cache::get('metrics:db:max_connections', 0)
            ],
            'tables' => $this->getTableMetrics($period)
        ];

        return response()->json($metrics);
    }

    /**
     * Cache-specific metrics
     */
    public function cacheMetrics(Request $request): JsonResponse
    {
        $period = $request->query('period', '1h');
        
        $metrics = [
            'timestamp' => now()->toISOString(),
            'period' => $period,
            'operations' => [
                'hits' => Cache::get('metrics:cache:hits', 0),
                'misses' => Cache::get('metrics:cache:misses', 0),
                'sets' => Cache::get('metrics:cache:sets', 0),
                'deletes' => Cache::get('metrics:cache:deletes', 0)
            ],
            'performance' => [
                'hit_ratio' => $this->calculateHitRatio(),
                'average_response_time' => Cache::get('metrics:cache:avg_response_time', 0)
            ],
            'memory' => [
                'used' => Cache::get('metrics:cache:memory_used', 0),
                'available' => Cache::get('metrics:cache:memory_available', 0)
            ],
            'top_keys' => $this->getTopCacheKeys($period)
        ];

        return response()->json($metrics);
    }

    /**
     * Authentication-specific metrics
     */
    public function authMetrics(Request $request): JsonResponse
    {
        $period = $request->query('period', '1h');
        
        $metrics = [
            'timestamp' => now()->toISOString(),
            'period' => $period,
            'authentication' => [
                'successful_logins' => Cache::get('metrics:auth:successful_logins', 0),
                'failed_logins' => Cache::get('metrics:auth:failed_logins', 0),
                'registrations' => Cache::get('metrics:auth:registrations', 0),
                'password_resets' => Cache::get('metrics:auth:password_resets', 0)
            ],
            'tokens' => [
                'issued' => Cache::get('metrics:auth:tokens_issued', 0),
                'refreshed' => Cache::get('metrics:auth:tokens_refreshed', 0),
                'revoked' => Cache::get('metrics:auth:tokens_revoked', 0)
            ],
            'users' => [
                'active_sessions' => Cache::get('metrics:auth:active_sessions', 0),
                'unique_users' => Cache::get('metrics:auth:unique_users', 0)
            ],
            'security' => [
                'brute_force_attempts' => Cache::get('metrics:auth:brute_force_attempts', 0),
                'account_lockouts' => Cache::get('metrics:auth:account_lockouts', 0)
            ]
        ];

        return response()->json($metrics);
    }

    /**
     * SSO-specific metrics
     */
    public function ssoMetrics(Request $request): JsonResponse
    {
        $period = $request->query('period', '1h');
        
        $metrics = [
            'timestamp' => now()->toISOString(),
            'period' => $period,
            'sessions' => [
                'active' => Cache::get('metrics:sso:active_sessions', 0),
                'created' => Cache::get('metrics:sso:sessions_created', 0),
                'expired' => Cache::get('metrics:sso:sessions_expired', 0),
                'revoked' => Cache::get('metrics:sso:sessions_revoked', 0)
            ],
            'services' => [
                'sahbandar' => Cache::get('metrics:sso:sahbandar_sessions', 0),
                'spb' => Cache::get('metrics:sso:spb_sessions', 0),
                'shti' => Cache::get('metrics:sso:shti_sessions', 0),
                'epit' => Cache::get('metrics:sso:epit_sessions', 0)
            ],
            'token_validation' => [
                'successful' => Cache::get('metrics:sso:token_validations_success', 0),
                'failed' => Cache::get('metrics:sso:token_validations_failed', 0)
            ],
            'cross_service_requests' => Cache::get('metrics:sso:cross_service_requests', 0)
        ];

        return response()->json($metrics);
    }

    /**
     * Security-specific metrics
     */
    public function securityMetrics(Request $request): JsonResponse
    {
        $period = $request->query('period', '1h');
        
        $metrics = [
            'timestamp' => now()->toISOString(),
            'period' => $period,
            'threats' => [
                'blocked_ips' => Cache::get('metrics:security:blocked_ips', 0),
                'suspicious_activities' => Cache::get('metrics:security:suspicious_activities', 0),
                'xss_attempts' => Cache::get('metrics:security:xss_attempts', 0),
                'sql_injection_attempts' => Cache::get('metrics:security:sql_injection_attempts', 0)
            ],
            'rate_limiting' => [
                'rate_limited_requests' => Cache::get('metrics:security:rate_limited_requests', 0),
                'unique_rate_limited_ips' => Cache::get('metrics:security:unique_rate_limited_ips', 0)
            ],
            'authentication_security' => [
                'brute_force_attempts' => Cache::get('metrics:security:brute_force_attempts', 0),
                'password_spray_attempts' => Cache::get('metrics:security:password_spray_attempts', 0),
                'account_lockouts' => Cache::get('metrics:security:account_lockouts', 0)
            ],
            'audit_events' => Cache::get('metrics:security:audit_events', 0)
        ];

        return response()->json($metrics);
    }

    /**
     * Performance-specific metrics
     */
    public function performanceMetrics(Request $request): JsonResponse
    {
        $period = $request->query('period', '1h');
        $performanceMetrics = $this->monitoringService->getPerformanceMetrics();
        
        return response()->json([
            'timestamp' => now()->toISOString(),
            'period' => $period,
            'metrics' => $performanceMetrics
        ]);
    }

    /**
     * Export metrics in various formats
     */
    public function exportMetrics(Request $request): JsonResponse
    {
        $format = $request->query('format', 'json'); // json, csv, prometheus
        $period = $request->query('period', '1h');
        
        $metrics = [
            'general' => $this->general($request)->getData(),
            'api' => $this->apiMetrics($request)->getData(),
            'database' => $this->databaseMetrics($request)->getData(),
            'cache' => $this->cacheMetrics($request)->getData(),
            'auth' => $this->authMetrics($request)->getData(),
            'sso' => $this->ssoMetrics($request)->getData(),
            'security' => $this->securityMetrics($request)->getData(),
            'performance' => $this->performanceMetrics($request)->getData()
        ];

        switch ($format) {
            case 'csv':
                return $this->exportToCsv($metrics);
            case 'prometheus':
                return $this->exportToPrometheus($metrics);
            default:
                return response()->json($metrics);
        }
    }

    // Helper methods
    private function getApiMetricsSummary(string $period): array
    {
        return [
            'total_requests' => Cache::get('metrics:api:total_requests', 0),
            'error_rate' => $this->calculateErrorRate(),
            'average_response_time' => Cache::get('metrics:api:avg_response_time', 0)
        ];
    }

    private function getDatabaseMetricsSummary(string $period): array
    {
        return [
            'total_queries' => Cache::get('metrics:db:total_queries', 0),
            'slow_queries' => Cache::get('metrics:db:slow_queries', 0),
            'average_query_time' => Cache::get('metrics:db:avg_query_time', 0)
        ];
    }

    private function getCacheMetricsSummary(string $period): array
    {
        return [
            'hit_ratio' => $this->calculateHitRatio(),
            'total_operations' => Cache::get('metrics:cache:hits', 0) + Cache::get('metrics:cache:misses', 0)
        ];
    }

    private function getAuthMetricsSummary(string $period): array
    {
        return [
            'successful_logins' => Cache::get('metrics:auth:successful_logins', 0),
            'failed_logins' => Cache::get('metrics:auth:failed_logins', 0),
            'active_sessions' => Cache::get('metrics:auth:active_sessions', 0)
        ];
    }

    private function getSSOMetricsSummary(string $period): array
    {
        return [
            'active_sessions' => Cache::get('metrics:sso:active_sessions', 0),
            'cross_service_requests' => Cache::get('metrics:sso:cross_service_requests', 0)
        ];
    }

    private function getSecurityMetricsSummary(string $period): array
    {
        return [
            'blocked_ips' => Cache::get('metrics:security:blocked_ips', 0),
            'suspicious_activities' => Cache::get('metrics:security:suspicious_activities', 0)
        ];
    }

    private function calculateErrorRate(): float
    {
        $total = Cache::get('metrics:api:total_requests', 0);
        $failed = Cache::get('metrics:api:failed_requests', 0);
        
        return $total > 0 ? round(($failed / $total) * 100, 2) : 0;
    }

    private function calculateHitRatio(): float
    {
        $hits = Cache::get('metrics:cache:hits', 0);
        $misses = Cache::get('metrics:cache:misses', 0);
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    private function getSystemUptime(): array
    {
        $uptime = exec('uptime');
        return ['raw' => $uptime];
    }

    private function getMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true)
        ];
    }

    private function getDiskUsage(): array
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        
        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'usage_percentage' => round(($used / $total) * 100, 2)
        ];
    }

    private function getTopEndpoints(string $period): array
    {
        // This would typically come from log analysis
        return Cache::get('metrics:api:top_endpoints', []);
    }

    private function getApiErrors(string $period): array
    {
        return Cache::get('metrics:api:errors', []);
    }

    private function getTableMetrics(string $period): array
    {
        return Cache::get('metrics:db:table_metrics', []);
    }

    private function getTopCacheKeys(string $period): array
    {
        return Cache::get('metrics:cache:top_keys', []);
    }

    private function exportToCsv(array $metrics): JsonResponse
    {
        // Implementation for CSV export
        return response()->json(['message' => 'CSV export not implemented yet']);
    }

    private function exportToPrometheus(array $metrics): JsonResponse
    {
        // Implementation for Prometheus format export
        return response()->json(['message' => 'Prometheus export not implemented yet']);
    }
}