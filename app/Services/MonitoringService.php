<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\SSOSession;

class MonitoringService
{
    private LoggingService $loggingService;

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * Get system health status
     */
    public function getSystemHealth(): array
    {
        $startTime = microtime(true);

        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'checks' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'storage' => $this->checkStorage(),
                'microservices' => $this->checkMicroservices(),
                'memory' => $this->checkMemoryUsage(),
                'disk' => $this->checkDiskUsage()
            ]
        ];

        // Determine overall status
        $failedChecks = array_filter($health['checks'], fn($check) => $check['status'] !== 'healthy');
        
        if (count($failedChecks) > 0) {
            $health['status'] = count($failedChecks) > 2 ? 'unhealthy' : 'degraded';
        }

        $health['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        // Log health check
        $this->loggingService->logPerformance('health_check', microtime(true) - $startTime, $health);

        return $health;
    }

    /**
     * Check database connectivity and performance
     */
    private function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);
            
            // Test basic connectivity
            DB::select('SELECT 1');
            
            // Test write operation
            $testResult = DB::table('audit_logs')->count();
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Check for slow queries or connection issues
            $status = 'healthy';
            if ($responseTime > 500) {
                $status = 'degraded';
            } elseif ($responseTime > 1000) {
                $status = 'unhealthy';
            }

            return [
                'status' => $status,
                'response_time_ms' => $responseTime,
                'connections' => $this->getDatabaseConnections(),
                'slow_queries' => $this->getSlowQueriesCount()
            ];
        } catch (\Exception $e) {
            Log::error('Database health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'response_time_ms' => null
            ];
        }
    }

    /**
     * Check cache (Redis) connectivity and performance
     */
    private function checkCache(): array
    {
        try {
            $startTime = microtime(true);
            
            // Test Redis connectivity
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test_value', 60);
            $value = Cache::get($testKey);
            Cache::forget($testKey);
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($value !== 'test_value') {
                throw new \Exception('Cache read/write test failed');
            }

            $status = $responseTime > 100 ? 'degraded' : 'healthy';

            return [
                'status' => $status,
                'response_time_ms' => $responseTime,
                'memory_usage' => $this->getRedisMemoryUsage(),
                'connected_clients' => $this->getRedisConnectedClients()
            ];
        } catch (\Exception $e) {
            Log::error('Cache health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'response_time_ms' => null
            ];
        }
    }

    /**
     * Check storage availability and disk space
     */
    private function checkStorage(): array
    {
        try {
            $storagePath = storage_path();
            $freeBytes = disk_free_space($storagePath);
            $totalBytes = disk_total_space($storagePath);
            $usedBytes = $totalBytes - $freeBytes;
            $usagePercent = round(($usedBytes / $totalBytes) * 100, 2);

            $status = 'healthy';
            if ($usagePercent > 85) {
                $status = 'degraded';
            } elseif ($usagePercent > 95) {
                $status = 'unhealthy';
            }

            return [
                'status' => $status,
                'usage_percent' => $usagePercent,
                'free_gb' => round($freeBytes / 1024 / 1024 / 1024, 2),
                'total_gb' => round($totalBytes / 1024 / 1024 / 1024, 2),
                'writable' => is_writable($storagePath)
            ];
        } catch (\Exception $e) {
            Log::error('Storage health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check microservices health
     */
    private function checkMicroservices(): array
    {
        $services = [
            'sahbandar' => config('services.sahbandar.base_url'),
            'spb' => config('services.spb.base_url'),
            'shti' => config('services.shti.base_url'),
            'epit' => config('services.epit.base_url')
        ];

        $results = [];
        $healthyCount = 0;

        foreach ($services as $name => $baseUrl) {
            try {
                $startTime = microtime(true);
                
                $response = Http::timeout(5)->get($baseUrl . '/health');
                
                $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                
                $status = $response->successful() ? 'healthy' : 'unhealthy';
                if ($status === 'healthy') {
                    $healthyCount++;
                }

                $results[$name] = [
                    'status' => $status,
                    'response_time_ms' => $responseTime,
                    'http_status' => $response->status()
                ];
            } catch (\Exception $e) {
                $results[$name] = [
                    'status' => 'unhealthy',
                    'error' => $e->getMessage(),
                    'response_time_ms' => null
                ];
            }
        }

        $overallStatus = 'healthy';
        if ($healthyCount === 0) {
            $overallStatus = 'unhealthy';
        } elseif ($healthyCount < count($services)) {
            $overallStatus = 'degraded';
        }

        return [
            'status' => $overallStatus,
            'healthy_services' => $healthyCount,
            'total_services' => count($services),
            'services' => $results
        ];
    }

    /**
     * Check memory usage
     */
    private function checkMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        $usagePercent = $memoryLimit > 0 ? round(($memoryUsage / $memoryLimit) * 100, 2) : 0;

        $status = 'healthy';
        if ($usagePercent > 80) {
            $status = 'degraded';
        } elseif ($usagePercent > 90) {
            $status = 'unhealthy';
        }

        return [
            'status' => $status,
            'usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'peak_mb' => round($memoryPeak / 1024 / 1024, 2),
            'limit_mb' => round($memoryLimit / 1024 / 1024, 2),
            'usage_percent' => $usagePercent
        ];
    }

    /**
     * Check disk usage
     */
    private function checkDiskUsage(): array
    {
        $path = base_path();
        $freeBytes = disk_free_space($path);
        $totalBytes = disk_total_space($path);
        $usedBytes = $totalBytes - $freeBytes;
        $usagePercent = round(($usedBytes / $totalBytes) * 100, 2);

        $status = 'healthy';
        if ($usagePercent > 85) {
            $status = 'degraded';
        } elseif ($usagePercent > 95) {
            $status = 'unhealthy';
        }

        return [
            'status' => $status,
            'usage_percent' => $usagePercent,
            'free_gb' => round($freeBytes / 1024 / 1024 / 1024, 2),
            'total_gb' => round($totalBytes / 1024 / 1024 / 1024, 2)
        ];
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'api' => $this->getApiMetrics(),
            'database' => $this->getDatabaseMetrics(),
            'cache' => $this->getCacheMetrics(),
            'authentication' => $this->getAuthMetrics(),
            'sso' => $this->getSSOMetrics()
        ];
    }

    /**
     * Get API performance metrics
     */
    private function getApiMetrics(): array
    {
        $cacheKey = 'api_metrics_' . now()->format('Y-m-d-H');
        
        return Cache::remember($cacheKey, 3600, function () {
            // In a real implementation, these would come from log analysis
            return [
                'requests_per_minute' => $this->getRequestsPerMinute(),
                'average_response_time_ms' => $this->getAverageResponseTime(),
                'error_rate_percent' => $this->getErrorRate(),
                'slowest_endpoints' => $this->getSlowestEndpoints()
            ];
        });
    }

    /**
     * Get database performance metrics
     */
    private function getDatabaseMetrics(): array
    {
        return [
            'active_connections' => $this->getDatabaseConnections(),
            'slow_queries_count' => $this->getSlowQueriesCount(),
            'average_query_time_ms' => $this->getAverageQueryTime(),
            'deadlocks_count' => $this->getDeadlocksCount()
        ];
    }

    /**
     * Get cache performance metrics
     */
    private function getCacheMetrics(): array
    {
        return [
            'hit_rate_percent' => $this->getCacheHitRate(),
            'memory_usage_mb' => $this->getRedisMemoryUsage(),
            'connected_clients' => $this->getRedisConnectedClients(),
            'operations_per_second' => $this->getRedisOperationsPerSecond()
        ];
    }

    /**
     * Get authentication metrics
     */
    private function getAuthMetrics(): array
    {
        $since = now()->subHour();
        
        return [
            'login_attempts' => AuditLog::where('action', 'login')
                ->where('created_at', '>=', $since)
                ->count(),
            'failed_logins' => AuditLog::where('action', 'login_failed')
                ->where('created_at', '>=', $since)
                ->count(),
            'active_sessions' => User::whereNotNull('last_login_at')
                ->where('last_login_at', '>=', now()->subDay())
                ->count(),
            'password_changes' => AuditLog::where('action', 'password_changed')
                ->where('created_at', '>=', $since)
                ->count()
        ];
    }

    /**
     * Get SSO metrics
     */
    private function getSSOMetrics(): array
    {
        return [
            'active_sessions' => SSOSession::where('is_active', true)
                ->where('expires_at', '>', now())
                ->count(),
            'sessions_by_service' => SSOSession::where('is_active', true)
                ->where('expires_at', '>', now())
                ->groupBy('service')
                ->selectRaw('service, count(*) as count')
                ->pluck('count', 'service')
                ->toArray(),
            'expired_sessions' => SSOSession::where('expires_at', '<=', now())
                ->where('is_active', true)
                ->count()
        ];
    }

    /**
     * Check for alerts and anomalies
     */
    public function checkAlerts(): array
    {
        $alerts = [];

        // Check for high error rates
        $errorRate = $this->getErrorRate();
        if ($errorRate > 5) {
            $alerts[] = [
                'type' => 'high_error_rate',
                'severity' => $errorRate > 10 ? 'critical' : 'warning',
                'message' => "High error rate detected: {$errorRate}%",
                'value' => $errorRate,
                'threshold' => 5
            ];
        }

        // Check for slow response times
        $avgResponseTime = $this->getAverageResponseTime();
        if ($avgResponseTime > 1000) {
            $alerts[] = [
                'type' => 'slow_response_time',
                'severity' => $avgResponseTime > 2000 ? 'critical' : 'warning',
                'message' => "Slow average response time: {$avgResponseTime}ms",
                'value' => $avgResponseTime,
                'threshold' => 1000
            ];
        }

        // Check for failed login attempts
        $failedLogins = AuditLog::where('action', 'login_failed')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();
        
        if ($failedLogins > 10) {
            $alerts[] = [
                'type' => 'high_failed_logins',
                'severity' => $failedLogins > 50 ? 'critical' : 'warning',
                'message' => "High number of failed login attempts: {$failedLogins} in last 15 minutes",
                'value' => $failedLogins,
                'threshold' => 10
            ];
        }

        // Check for disk space
        $diskUsage = $this->checkDiskUsage();
        if ($diskUsage['usage_percent'] > 85) {
            $alerts[] = [
                'type' => 'high_disk_usage',
                'severity' => $diskUsage['usage_percent'] > 95 ? 'critical' : 'warning',
                'message' => "High disk usage: {$diskUsage['usage_percent']}%",
                'value' => $diskUsage['usage_percent'],
                'threshold' => 85
            ];
        }

        // Check for memory usage
        $memoryUsage = $this->checkMemoryUsage();
        if ($memoryUsage['usage_percent'] > 80) {
            $alerts[] = [
                'type' => 'high_memory_usage',
                'severity' => $memoryUsage['usage_percent'] > 90 ? 'critical' : 'warning',
                'message' => "High memory usage: {$memoryUsage['usage_percent']}%",
                'value' => $memoryUsage['usage_percent'],
                'threshold' => 80
            ];
        }

        return $alerts;
    }

    /**
     * Helper methods for metrics calculation
     */
    private function getDatabaseConnections(): int
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            return (int) $result[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getSlowQueriesCount(): int
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Slow_queries'");
            return (int) $result[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getAverageQueryTime(): float
    {
        // This would typically come from query log analysis
        return 25.5; // Placeholder
    }

    private function getDeadlocksCount(): int
    {
        // This would typically come from database monitoring
        return 0; // Placeholder
    }

    private function getRedisMemoryUsage(): float
    {
        try {
            $info = Redis::info('memory');
            return round($info['used_memory'] / 1024 / 1024, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getRedisConnectedClients(): int
    {
        try {
            $info = Redis::info('clients');
            return (int) $info['connected_clients'];
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getCacheHitRate(): float
    {
        try {
            $info = Redis::info('stats');
            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;
            $total = $hits + $misses;
            
            return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getRedisOperationsPerSecond(): float
    {
        // This would typically be calculated from Redis monitoring
        return 150.5; // Placeholder
    }

    private function getRequestsPerMinute(): int
    {
        // This would come from access log analysis
        return 45; // Placeholder
    }

    private function getAverageResponseTime(): float
    {
        // This would come from access log analysis
        return 250.5; // Placeholder
    }

    private function getErrorRate(): float
    {
        // This would come from access log analysis
        return 2.1; // Placeholder
    }

    private function getSlowestEndpoints(): array
    {
        // This would come from access log analysis
        return [
            '/api/users' => 450.2,
            '/api/services/sahbandar/profile' => 380.1,
            '/api/auth/login' => 320.5
        ];
    }

    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $limit = (int) $limit;

        switch ($last) {
            case 'g':
                $limit *= 1024;
            case 'm':
                $limit *= 1024;
            case 'k':
                $limit *= 1024;
        }

        return $limit;
    }
}