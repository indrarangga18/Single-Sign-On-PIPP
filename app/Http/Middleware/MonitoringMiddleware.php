<?php

namespace App\Http\Middleware;

use App\Services\MonitoringService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class MonitoringMiddleware
{
    private MonitoringService $monitoringService;

    public function __construct(MonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        // Calculate metrics
        $duration = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;

        // Collect performance metrics
        $this->collectPerformanceMetrics($request, $response, $duration, $memoryUsage);

        // Check for alerts
        $this->checkAlertConditions($request, $response, $duration);

        // Update system metrics
        $this->updateSystemMetrics($duration, $memoryUsage);

        return $response;
    }

    /**
     * Collect performance metrics
     */
    private function collectPerformanceMetrics(
        Request $request,
        Response $response,
        float $duration,
        int $memoryUsage
    ): void {
        $endpoint = $this->getEndpointIdentifier($request);
        
        // Store API performance metrics
        $this->monitoringService->recordApiMetrics($endpoint, [
            'response_time' => $duration,
            'status_code' => $response->getStatusCode(),
            'memory_usage' => $memoryUsage,
            'timestamp' => now()->toISOString()
        ]);

        // Track response time percentiles
        $this->updateResponseTimePercentiles($endpoint, $duration);

        // Track error rates
        if ($response->getStatusCode() >= 400) {
            $this->incrementErrorCount($endpoint, $response->getStatusCode());
        }

        // Track throughput
        $this->incrementThroughputCounter($endpoint);
    }

    /**
     * Check for alert conditions
     */
    private function checkAlertConditions(Request $request, Response $response, float $duration): void
    {
        $endpoint = $this->getEndpointIdentifier($request);

        // Check for slow responses
        if ($duration > 2.0) { // 2 seconds threshold
            $this->monitoringService->triggerAlert('slow_response', [
                'endpoint' => $endpoint,
                'duration' => $duration,
                'threshold' => 2.0,
                'severity' => $duration > 5.0 ? 'critical' : 'warning'
            ]);
        }

        // Check for high error rates
        $errorRate = $this->calculateErrorRate($endpoint);
        if ($errorRate > 0.1) { // 10% error rate threshold
            $this->monitoringService->triggerAlert('high_error_rate', [
                'endpoint' => $endpoint,
                'error_rate' => $errorRate,
                'threshold' => 0.1,
                'severity' => $errorRate > 0.25 ? 'critical' : 'warning'
            ]);
        }

        // Check for authentication failures
        if ($response->getStatusCode() === 401) {
            $this->incrementAuthFailureCount();
        }

        // Check for rate limit hits
        if ($response->getStatusCode() === 429) {
            $this->monitoringService->triggerAlert('rate_limit_exceeded', [
                'endpoint' => $endpoint,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
        }
    }

    /**
     * Update system-wide metrics
     */
    private function updateSystemMetrics(float $duration, int $memoryUsage): void
    {
        // Update average response time
        $this->updateAverageResponseTime($duration);

        // Update memory usage statistics
        $this->updateMemoryUsageStats($memoryUsage);

        // Update request count
        $this->incrementRequestCount();

        // Check system health periodically
        if (rand(1, 100) === 1) { // 1% chance to run health check
            $this->runSystemHealthCheck();
        }
    }

    /**
     * Get endpoint identifier for metrics
     */
    private function getEndpointIdentifier(Request $request): string
    {
        $route = $request->route();
        
        if ($route) {
            return $route->getName() ?: $request->method() . ' ' . $route->uri();
        }

        return $request->method() . ' ' . $request->path();
    }

    /**
     * Update response time percentiles
     */
    private function updateResponseTimePercentiles(string $endpoint, float $duration): void
    {
        $key = "response_times:{$endpoint}";
        $times = Cache::get($key, []);
        
        $times[] = $duration;
        
        // Keep only last 1000 measurements
        if (count($times) > 1000) {
            $times = array_slice($times, -1000);
        }
        
        Cache::put($key, $times, 3600); // Cache for 1 hour
        
        // Calculate and store percentiles
        if (count($times) >= 10) {
            sort($times);
            $percentiles = [
                'p50' => $this->calculatePercentile($times, 50),
                'p90' => $this->calculatePercentile($times, 90),
                'p95' => $this->calculatePercentile($times, 95),
                'p99' => $this->calculatePercentile($times, 99)
            ];
            
            Cache::put("percentiles:{$endpoint}", $percentiles, 3600);
        }
    }

    /**
     * Calculate percentile from array of values
     */
    private function calculatePercentile(array $values, int $percentile): float
    {
        $index = ($percentile / 100) * (count($values) - 1);
        $lower = floor($index);
        $upper = ceil($index);
        
        if ($lower === $upper) {
            return $values[$lower];
        }
        
        return $values[$lower] + ($index - $lower) * ($values[$upper] - $values[$lower]);
    }

    /**
     * Increment error count for endpoint
     */
    private function incrementErrorCount(string $endpoint, int $statusCode): void
    {
        $minute = now()->format('Y-m-d-H-i');
        $errorKey = "errors:{$endpoint}:{$minute}";
        $statusKey = "status:{$endpoint}:{$statusCode}:{$minute}";
        
        Cache::increment($errorKey, 1);
        Cache::increment($statusKey, 1);
        
        // Set expiration
        Cache::put($errorKey, Cache::get($errorKey, 0), 3600);
        Cache::put($statusKey, Cache::get($statusKey, 0), 3600);
    }

    /**
     * Increment throughput counter
     */
    private function incrementThroughputCounter(string $endpoint): void
    {
        $minute = now()->format('Y-m-d-H-i');
        $key = "throughput:{$endpoint}:{$minute}";
        
        Cache::increment($key, 1);
        Cache::put($key, Cache::get($key, 0), 3600);
    }

    /**
     * Calculate error rate for endpoint
     */
    private function calculateErrorRate(string $endpoint): float
    {
        $minute = now()->format('Y-m-d-H-i');
        $errorKey = "errors:{$endpoint}:{$minute}";
        $throughputKey = "throughput:{$endpoint}:{$minute}";
        
        $errors = Cache::get($errorKey, 0);
        $total = Cache::get($throughputKey, 0);
        
        return $total > 0 ? $errors / $total : 0;
    }

    /**
     * Increment authentication failure count
     */
    private function incrementAuthFailureCount(): void
    {
        $minute = now()->format('Y-m-d-H-i');
        $key = "auth_failures:{$minute}";
        
        Cache::increment($key, 1);
        Cache::put($key, Cache::get($key, 0), 3600);
        
        // Check for brute force attacks
        $failures = Cache::get($key, 0);
        if ($failures > 50) { // More than 50 failures per minute
            $this->monitoringService->triggerAlert('potential_brute_force', [
                'failures_per_minute' => $failures,
                'threshold' => 50,
                'severity' => 'critical'
            ]);
        }
    }

    /**
     * Update average response time
     */
    private function updateAverageResponseTime(float $duration): void
    {
        $key = 'avg_response_time';
        $current = Cache::get($key, ['total' => 0, 'count' => 0]);
        
        $current['total'] += $duration;
        $current['count']++;
        
        // Reset every hour to prevent overflow
        if ($current['count'] > 10000) {
            $current = ['total' => $duration, 'count' => 1];
        }
        
        Cache::put($key, $current, 3600);
    }

    /**
     * Update memory usage statistics
     */
    private function updateMemoryUsageStats(int $memoryUsage): void
    {
        $key = 'memory_stats';
        $stats = Cache::get($key, ['peak' => 0, 'average' => 0, 'count' => 0]);
        
        $stats['peak'] = max($stats['peak'], $memoryUsage);
        $stats['average'] = (($stats['average'] * $stats['count']) + $memoryUsage) / ($stats['count'] + 1);
        $stats['count']++;
        
        // Reset every hour
        if ($stats['count'] > 10000) {
            $stats = ['peak' => $memoryUsage, 'average' => $memoryUsage, 'count' => 1];
        }
        
        Cache::put($key, $stats, 3600);
    }

    /**
     * Increment total request count
     */
    private function incrementRequestCount(): void
    {
        $minute = now()->format('Y-m-d-H-i');
        $key = "total_requests:{$minute}";
        
        Cache::increment($key, 1);
        Cache::put($key, Cache::get($key, 0), 3600);
    }

    /**
     * Run system health check
     */
    private function runSystemHealthCheck(): void
    {
        $health = $this->monitoringService->getSystemHealth();
        
        foreach ($health as $component => $status) {
            if ($status['status'] !== 'healthy') {
                $this->monitoringService->triggerAlert('system_health_issue', [
                    'component' => $component,
                    'status' => $status['status'],
                    'details' => $status,
                    'severity' => $status['status'] === 'critical' ? 'critical' : 'warning'
                ]);
            }
        }
    }
}