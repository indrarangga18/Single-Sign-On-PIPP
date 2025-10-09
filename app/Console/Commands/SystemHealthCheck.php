<?php

namespace App\Console\Commands;

use App\Services\MonitoringService;
use App\Services\LoggingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class SystemHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'system:health-check 
                            {--detailed : Show detailed health information}
                            {--json : Output results in JSON format}
                            {--alert : Send alerts for unhealthy components}';

    /**
     * The console command description.
     */
    protected $description = 'Perform comprehensive system health check';

    private MonitoringService $monitoringService;
    private LoggingService $loggingService;

    public function __construct(MonitoringService $monitoringService, LoggingService $loggingService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
        $this->loggingService = $loggingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting system health check...');
        
        $startTime = microtime(true);
        $healthResults = [];

        // Run all health checks
        $healthResults['database'] = $this->checkDatabase();
        $healthResults['cache'] = $this->checkCache();
        $healthResults['storage'] = $this->checkStorage();
        $healthResults['microservices'] = $this->checkMicroservices();
        $healthResults['system_resources'] = $this->checkSystemResources();
        $healthResults['security'] = $this->checkSecurity();
        $healthResults['performance'] = $this->checkPerformance();

        $duration = microtime(true) - $startTime;
        $overallStatus = $this->calculateOverallStatus($healthResults);

        // Log health check results
        $this->loggingService->logSystemEvent('health_check_completed', [
            'duration' => $duration,
            'overall_status' => $overallStatus,
            'results' => $healthResults
        ]);

        // Output results
        if ($this->option('json')) {
            $this->outputJson($healthResults, $overallStatus, $duration);
        } else {
            $this->outputTable($healthResults, $overallStatus, $duration);
        }

        // Send alerts if requested
        if ($this->option('alert')) {
            $this->sendAlerts($healthResults);
        }

        return $overallStatus === 'healthy' ? 0 : 1;
    }

    /**
     * Check database connectivity and performance
     */
    private function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);
            
            // Test basic connectivity
            DB::connection()->getPdo();
            $connectionTime = microtime(true) - $startTime;

            // Test query performance
            $queryStartTime = microtime(true);
            $userCount = DB::table('users')->count();
            $queryTime = microtime(true) - $queryStartTime;

            // Check for long-running queries
            $longQueries = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.processlist 
                WHERE command != 'Sleep' AND time > 30
            ");

            // Check database size
            $dbSize = DB::select("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ");

            $status = 'healthy';
            $issues = [];

            if ($connectionTime > 1.0) {
                $status = 'warning';
                $issues[] = 'Slow database connection';
            }

            if ($queryTime > 0.5) {
                $status = 'warning';
                $issues[] = 'Slow query performance';
            }

            if ($longQueries[0]->count > 0) {
                $status = 'critical';
                $issues[] = 'Long-running queries detected';
            }

            return [
                'status' => $status,
                'connection_time' => $connectionTime,
                'query_time' => $queryTime,
                'user_count' => $userCount,
                'long_queries' => $longQueries[0]->count,
                'database_size_mb' => $dbSize[0]->size_mb ?? 0,
                'issues' => $issues
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'issues' => ['Database connection failed']
            ];
        }
    }

    /**
     * Check cache system health
     */
    private function checkCache(): array
    {
        try {
            $startTime = microtime(true);
            
            // Test cache write/read
            $testKey = 'health_check_' . time();
            $testValue = 'test_value_' . rand(1000, 9999);
            
            Cache::put($testKey, $testValue, 60);
            $retrievedValue = Cache::get($testKey);
            Cache::forget($testKey);
            
            $operationTime = microtime(true) - $startTime;

            // Get cache statistics if available (Redis)
            $stats = [];
            try {
                if (config('cache.default') === 'redis') {
                    $redis = Cache::getRedis();
                    $info = $redis->info();
                    $stats = [
                        'used_memory' => $info['used_memory_human'] ?? 'N/A',
                        'connected_clients' => $info['connected_clients'] ?? 'N/A',
                        'total_commands_processed' => $info['total_commands_processed'] ?? 'N/A'
                    ];
                }
            } catch (\Exception $e) {
                // Redis info not available
            }

            $status = 'healthy';
            $issues = [];

            if ($retrievedValue !== $testValue) {
                $status = 'critical';
                $issues[] = 'Cache read/write test failed';
            }

            if ($operationTime > 0.1) {
                $status = 'warning';
                $issues[] = 'Slow cache operations';
            }

            return [
                'status' => $status,
                'operation_time' => $operationTime,
                'driver' => config('cache.default'),
                'stats' => $stats,
                'issues' => $issues
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'issues' => ['Cache system unavailable']
            ];
        }
    }

    /**
     * Check storage system health
     */
    private function checkStorage(): array
    {
        try {
            $results = [];
            $overallStatus = 'healthy';
            $issues = [];

            // Check each configured disk
            $disks = ['local', 'public'];
            
            foreach ($disks as $disk) {
                try {
                    $startTime = microtime(true);
                    
                    // Test write operation
                    $testFile = 'health_check_' . time() . '.txt';
                    $testContent = 'Health check test content';
                    
                    Storage::disk($disk)->put($testFile, $testContent);
                    $retrievedContent = Storage::disk($disk)->get($testFile);
                    Storage::disk($disk)->delete($testFile);
                    
                    $operationTime = microtime(true) - $startTime;

                    // Get disk space information
                    $path = Storage::disk($disk)->path('');
                    $freeBytes = disk_free_space($path);
                    $totalBytes = disk_total_space($path);
                    $usedPercent = (($totalBytes - $freeBytes) / $totalBytes) * 100;

                    $diskStatus = 'healthy';
                    $diskIssues = [];

                    if ($retrievedContent !== $testContent) {
                        $diskStatus = 'critical';
                        $diskIssues[] = 'File read/write test failed';
                        $overallStatus = 'critical';
                    }

                    if ($operationTime > 1.0) {
                        $diskStatus = 'warning';
                        $diskIssues[] = 'Slow file operations';
                        if ($overallStatus === 'healthy') {
                            $overallStatus = 'warning';
                        }
                    }

                    if ($usedPercent > 90) {
                        $diskStatus = 'critical';
                        $diskIssues[] = 'Disk space critically low';
                        $overallStatus = 'critical';
                    } elseif ($usedPercent > 80) {
                        $diskStatus = 'warning';
                        $diskIssues[] = 'Disk space running low';
                        if ($overallStatus === 'healthy') {
                            $overallStatus = 'warning';
                        }
                    }

                    $results[$disk] = [
                        'status' => $diskStatus,
                        'operation_time' => $operationTime,
                        'free_space_gb' => round($freeBytes / 1024 / 1024 / 1024, 2),
                        'total_space_gb' => round($totalBytes / 1024 / 1024 / 1024, 2),
                        'used_percent' => round($usedPercent, 2),
                        'issues' => $diskIssues
                    ];

                    $issues = array_merge($issues, $diskIssues);

                } catch (\Exception $e) {
                    $results[$disk] = [
                        'status' => 'critical',
                        'error' => $e->getMessage(),
                        'issues' => ['Storage disk unavailable']
                    ];
                    $overallStatus = 'critical';
                    $issues[] = "Storage disk '{$disk}' unavailable";
                }
            }

            return [
                'status' => $overallStatus,
                'disks' => $results,
                'issues' => $issues
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'issues' => ['Storage system check failed']
            ];
        }
    }

    /**
     * Check microservices connectivity
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
        $overallStatus = 'healthy';
        $issues = [];

        foreach ($services as $service => $baseUrl) {
            if (!$baseUrl) {
                $results[$service] = [
                    'status' => 'warning',
                    'error' => 'Service URL not configured',
                    'issues' => ['Service not configured']
                ];
                if ($overallStatus === 'healthy') {
                    $overallStatus = 'warning';
                }
                $issues[] = "Service '{$service}' not configured";
                continue;
            }

            try {
                $startTime = microtime(true);
                
                $response = Http::timeout(10)->get($baseUrl . '/health');
                $responseTime = microtime(true) - $startTime;

                $serviceStatus = 'healthy';
                $serviceIssues = [];

                if (!$response->successful()) {
                    $serviceStatus = 'critical';
                    $serviceIssues[] = 'Service returned error status';
                    $overallStatus = 'critical';
                }

                if ($responseTime > 5.0) {
                    $serviceStatus = 'warning';
                    $serviceIssues[] = 'Slow response time';
                    if ($overallStatus === 'healthy') {
                        $overallStatus = 'warning';
                    }
                }

                $results[$service] = [
                    'status' => $serviceStatus,
                    'response_time' => $responseTime,
                    'http_status' => $response->status(),
                    'url' => $baseUrl,
                    'issues' => $serviceIssues
                ];

                $issues = array_merge($issues, $serviceIssues);

            } catch (\Exception $e) {
                $results[$service] = [
                    'status' => 'critical',
                    'error' => $e->getMessage(),
                    'url' => $baseUrl,
                    'issues' => ['Service unreachable']
                ];
                $overallStatus = 'critical';
                $issues[] = "Service '{$service}' unreachable";
            }
        }

        return [
            'status' => $overallStatus,
            'services' => $results,
            'issues' => $issues
        ];
    }

    /**
     * Check system resources
     */
    private function checkSystemResources(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        $memoryPercent = ($memoryUsage / $memoryLimit) * 100;
        $peakPercent = ($memoryPeak / $memoryLimit) * 100;

        $status = 'healthy';
        $issues = [];

        if ($memoryPercent > 90) {
            $status = 'critical';
            $issues[] = 'Memory usage critically high';
        } elseif ($memoryPercent > 80) {
            $status = 'warning';
            $issues[] = 'Memory usage high';
        }

        if ($peakPercent > 95) {
            $status = 'critical';
            $issues[] = 'Peak memory usage critically high';
        }

        return [
            'status' => $status,
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'memory_peak_mb' => round($memoryPeak / 1024 / 1024, 2),
            'memory_limit_mb' => round($memoryLimit / 1024 / 1024, 2),
            'memory_usage_percent' => round($memoryPercent, 2),
            'peak_usage_percent' => round($peakPercent, 2),
            'issues' => $issues
        ];
    }

    /**
     * Check security-related health
     */
    private function checkSecurity(): array
    {
        $issues = [];
        $status = 'healthy';

        // Check if debug mode is enabled in production
        if (app()->environment('production') && config('app.debug')) {
            $status = 'critical';
            $issues[] = 'Debug mode enabled in production';
        }

        // Check if HTTPS is enforced
        if (app()->environment('production') && !request()->isSecure()) {
            $status = 'warning';
            $issues[] = 'HTTPS not enforced';
        }

        // Check JWT secret
        if (empty(config('jwt.secret'))) {
            $status = 'critical';
            $issues[] = 'JWT secret not configured';
        }

        // Check session configuration
        if (config('session.secure') === false && app()->environment('production')) {
            $status = 'warning';
            $issues[] = 'Secure session cookies not enabled';
        }

        // Check for recent failed login attempts
        $recentFailures = Cache::get('auth_failures:' . now()->format('Y-m-d-H-i'), 0);
        if ($recentFailures > 100) {
            $status = 'critical';
            $issues[] = 'High number of authentication failures detected';
        }

        return [
            'status' => $status,
            'debug_mode' => config('app.debug'),
            'https_enabled' => request()->isSecure(),
            'jwt_configured' => !empty(config('jwt.secret')),
            'secure_cookies' => config('session.secure'),
            'recent_auth_failures' => $recentFailures,
            'issues' => $issues
        ];
    }

    /**
     * Check performance metrics
     */
    private function checkPerformance(): array
    {
        $avgResponseTime = Cache::get('avg_response_time', ['total' => 0, 'count' => 0]);
        $memoryStats = Cache::get('memory_stats', ['peak' => 0, 'average' => 0, 'count' => 0]);

        $avgTime = $avgResponseTime['count'] > 0 
            ? $avgResponseTime['total'] / $avgResponseTime['count'] 
            : 0;

        $status = 'healthy';
        $issues = [];

        if ($avgTime > 2.0) {
            $status = 'critical';
            $issues[] = 'Average response time too high';
        } elseif ($avgTime > 1.0) {
            $status = 'warning';
            $issues[] = 'Average response time elevated';
        }

        if ($memoryStats['peak'] > 128 * 1024 * 1024) { // 128MB
            $status = 'warning';
            $issues[] = 'High peak memory usage';
        }

        return [
            'status' => $status,
            'avg_response_time' => round($avgTime, 3),
            'peak_memory_mb' => round($memoryStats['peak'] / 1024 / 1024, 2),
            'avg_memory_mb' => round($memoryStats['average'] / 1024 / 1024, 2),
            'request_count' => $avgResponseTime['count'],
            'issues' => $issues
        ];
    }

    /**
     * Calculate overall system status
     */
    private function calculateOverallStatus(array $healthResults): string
    {
        $statuses = array_column($healthResults, 'status');

        if (in_array('critical', $statuses)) {
            return 'critical';
        }

        if (in_array('warning', $statuses)) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Output results as JSON
     */
    private function outputJson(array $healthResults, string $overallStatus, float $duration): void
    {
        $output = [
            'timestamp' => now()->toISOString(),
            'overall_status' => $overallStatus,
            'check_duration' => round($duration, 3),
            'components' => $healthResults
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT));
    }

    /**
     * Output results as table
     */
    private function outputTable(array $healthResults, string $overallStatus, float $duration): void
    {
        $this->newLine();
        $this->info("System Health Check Results");
        $this->info("Overall Status: " . strtoupper($overallStatus));
        $this->info("Check Duration: " . round($duration, 3) . "s");
        $this->newLine();

        $tableData = [];
        foreach ($healthResults as $component => $result) {
            $status = $result['status'];
            $issues = implode(', ', $result['issues'] ?? []);
            
            $tableData[] = [
                'Component' => ucfirst(str_replace('_', ' ', $component)),
                'Status' => strtoupper($status),
                'Issues' => $issues ?: 'None'
            ];
        }

        $this->table(['Component', 'Status', 'Issues'], $tableData);

        if ($this->option('detailed')) {
            $this->newLine();
            $this->info("Detailed Results:");
            foreach ($healthResults as $component => $result) {
                $this->info("\n" . strtoupper($component) . ":");
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
            }
        }
    }

    /**
     * Send alerts for unhealthy components
     */
    private function sendAlerts(array $healthResults): void
    {
        foreach ($healthResults as $component => $result) {
            if ($result['status'] !== 'healthy') {
                $this->monitoringService->triggerAlert('health_check_failure', [
                    'component' => $component,
                    'status' => $result['status'],
                    'issues' => $result['issues'] ?? [],
                    'details' => $result
                ]);
            }
        }
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

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