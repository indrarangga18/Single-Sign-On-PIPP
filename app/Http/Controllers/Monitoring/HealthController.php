<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Services\MonitoringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    protected MonitoringService $monitoringService;

    public function __construct(MonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    /**
     * Basic health check endpoint
     */
    public function basic(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'service' => 'SSO-PIPP',
            'version' => config('app.version', '1.0.0')
        ]);
    }

    /**
     * Detailed health check with authentication required
     */
    public function detailed(): JsonResponse
    {
        $healthChecks = $this->monitoringService->getSystemHealth();
        
        $overallStatus = collect($healthChecks)->every(function ($check) {
            return $check['status'] === 'healthy';
        }) ? 'healthy' : 'unhealthy';

        return response()->json([
            'status' => $overallStatus,
            'timestamp' => now()->toISOString(),
            'service' => 'SSO-PIPP',
            'version' => config('app.version', '1.0.0'),
            'checks' => $healthChecks
        ], $overallStatus === 'healthy' ? 200 : 503);
    }

    /**
     * System health overview
     */
    public function systemHealth(): JsonResponse
    {
        $healthChecks = $this->monitoringService->getSystemHealth();
        $performanceMetrics = $this->monitoringService->getPerformanceMetrics();
        
        $overallStatus = collect($healthChecks)->every(function ($check) {
            return $check['status'] === 'healthy';
        }) ? 'healthy' : 'unhealthy';

        return response()->json([
            'status' => $overallStatus,
            'timestamp' => now()->toISOString(),
            'health_checks' => $healthChecks,
            'performance' => [
                'api' => $performanceMetrics['api'] ?? [],
                'database' => $performanceMetrics['database'] ?? [],
                'cache' => $performanceMetrics['cache'] ?? []
            ],
            'uptime' => $this->getSystemUptime(),
            'memory_usage' => $this->getMemoryUsage()
        ], $overallStatus === 'healthy' ? 200 : 503);
    }

    /**
     * Individual component health checks
     */
    public function componentHealth(Request $request): JsonResponse
    {
        $component = $request->query('component');
        
        if ($component) {
            $healthCheck = $this->monitoringService->checkComponentHealth($component);
            
            if (!$healthCheck) {
                return response()->json([
                    'error' => 'Component not found',
                    'available_components' => ['database', 'cache', 'storage', 'microservices']
                ], 404);
            }
            
            return response()->json([
                'component' => $component,
                'timestamp' => now()->toISOString(),
                'health' => $healthCheck
            ], $healthCheck['status'] === 'healthy' ? 200 : 503);
        }

        $healthChecks = $this->monitoringService->getSystemHealth();
        
        return response()->json([
            'timestamp' => now()->toISOString(),
            'components' => $healthChecks
        ]);
    }

    /**
     * Get system uptime
     */
    private function getSystemUptime(): array
    {
        $uptime = exec('uptime');
        
        return [
            'raw' => $uptime,
            'load_average' => $this->parseLoadAverage($uptime)
        ];
    }

    /**
     * Get memory usage information
     */
    private function getMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit'),
            'usage_percentage' => round((memory_get_usage(true) / $this->convertToBytes(ini_get('memory_limit'))) * 100, 2)
        ];
    }

    /**
     * Parse load average from uptime command
     */
    private function parseLoadAverage(string $uptime): array
    {
        if (preg_match('/load averages?: ([\d.]+),?\s+([\d.]+),?\s+([\d.]+)/', $uptime, $matches)) {
            return [
                '1min' => (float) $matches[1],
                '5min' => (float) $matches[2],
                '15min' => (float) $matches[3]
            ];
        }
        
        return [];
    }

    /**
     * Convert memory limit string to bytes
     */
    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}