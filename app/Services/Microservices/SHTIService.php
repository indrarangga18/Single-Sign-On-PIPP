<?php

namespace App\Services\Microservices;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SHTIService
{
    protected $baseUrl;
    protected $apiKey;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.shti.base_url', 'http://shti-service:8080/api');
        $this->apiKey = config('services.shti.api_key');
        $this->timeout = config('services.shti.timeout', 30);
    }

    /**
     * Get fishing catch reports.
     */
    public function getCatchReports(array $params = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl . '/catch-reports', $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('SHTI API error: ' . $response->body());
            throw new \Exception('Failed to fetch catch reports from SHTI service');

        } catch (\Exception $e) {
            Log::error('SHTI service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get specific catch report.
     */
    public function getCatchReport(string $reportId): ?array
    {
        try {
            $cacheKey = "shti_catch_report_{$reportId}";
            
            return Cache::remember($cacheKey, 300, function () use ($reportId) {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . "/catch-reports/{$reportId}");

                if ($response->successful()) {
                    return $response->json();
                }

                if ($response->status() === 404) {
                    return null;
                }

                Log::error('SHTI API error: ' . $response->body());
                throw new \Exception('Failed to fetch catch report details');
            });

        } catch (\Exception $e) {
            Log::error('SHTI service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create new catch report.
     */
    public function createCatchReport(array $reportData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/catch-reports', $reportData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('SHTI API error: ' . $response->body());
            throw new \Exception('Failed to create catch report');

        } catch (\Exception $e) {
            Log::error('SHTI service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update catch report status.
     */
    public function updateReportStatus(string $reportId, array $updateData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->put($this->baseUrl . "/catch-reports/{$reportId}/status", $updateData);

            if ($response->successful()) {
                // Clear cache for this report
                Cache::forget("shti_catch_report_{$reportId}");
                
                return $response->json();
            }

            Log::error('SHTI API error: ' . $response->body());
            throw new \Exception('Failed to update catch report status');

        } catch (\Exception $e) {
            Log::error('SHTI service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get fishing vessels.
     */
    public function getFishingVessels(array $params = []): array
    {
        try {
            $cacheKey = 'shti_fishing_vessels_' . md5(serialize($params));
            
            return Cache::remember($cacheKey, 600, function () use ($params) {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . '/fishing-vessels', $params);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('SHTI API error: ' . $response->body());
                throw new \Exception('Failed to fetch fishing vessels');
            });

        } catch (\Exception $e) {
            Log::error('SHTI service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get fishing quotas.
     */
    public function getFishingQuotas(array $params = []): array
    {
        try {
            $cacheKey = 'shti_fishing_quotas_' . md5(serialize($params));
            
            return Cache::remember($cacheKey, 1800, function () use ($params) {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . '/fishing-quotas', $params);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('SHTI API error: ' . $response->body());
                throw new \Exception('Failed to fetch fishing quotas');
            });

        } catch (\Exception $e) {
            Log::error('SHTI service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get catch statistics.
     */
    public function getCatchStatistics(array $params = []): array
    {
        try {
            $cacheKey = 'shti_catch_statistics_' . md5(serialize($params));
            
            return Cache::remember($cacheKey, 900, function () use ($params) {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . '/catch-statistics', $params);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('SHTI API error: ' . $response->body());
                throw new \Exception('Failed to fetch catch statistics');
            });

        } catch (\Exception $e) {
            Log::error('SHTI service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate fishing license.
     */
    public function validateFishingLicense(string $licenseNumber): array
    {
        try {
            $cacheKey = "shti_license_validation_{$licenseNumber}";
            
            return Cache::remember($cacheKey, 3600, function () use ($licenseNumber) {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . "/fishing-licenses/{$licenseNumber}/validate");

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('SHTI API error: ' . $response->body());
                throw new \Exception('Failed to validate fishing license');
            });

        } catch (\Exception $e) {
            Log::error('SHTI service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get fishing areas.
     */
    public function getFishingAreas(array $params = []): array
    {
        try {
            $cacheKey = 'shti_fishing_areas_' . md5(serialize($params));
            
            return Cache::remember($cacheKey, 3600, function () use ($params) {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . '/fishing-areas', $params);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('SHTI API error: ' . $response->body());
                throw new \Exception('Failed to fetch fishing areas');
            });

        } catch (\Exception $e) {
            Log::error('SHTI service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get dashboard statistics.
     */
    public function getDashboardStats(): array
    {
        try {
            $cacheKey = 'shti_dashboard_stats';
            
            return Cache::remember($cacheKey, 300, function () {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . '/dashboard/stats');

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('SHTI API error: ' . $response->body());
                throw new \Exception('Failed to fetch dashboard statistics from SHTI service');
            });

        } catch (\Exception $e) {
            Log::error('SHTI service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate SHTI report.
     */
    public function generateReport(array $reportParams): array
    {
        try {
            $response = Http::timeout(120) // Longer timeout for report generation
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/reports/generate', $reportParams);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('SHTI API error: ' . $response->body());
            throw new \Exception('Failed to generate SHTI report');

        } catch (\Exception $e) {
            Log::error('SHTI service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync data with SHTI service.
     */
    public function syncData(): array
    {
        try {
            $response = Http::timeout(120) // Longer timeout for sync operations
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl . '/sync');

            if ($response->successful()) {
                // Clear related caches after sync
                Cache::forget('shti_dashboard_stats');
                
                return $response->json();
            }

            Log::error('SHTI API error: ' . $response->body());
            throw new \Exception('Failed to sync data with SHTI service');

        } catch (\Exception $e) {
            Log::error('SHTI service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check service health.
     */
    public function checkHealth(): bool
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl . '/health');

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('SHTI health check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get service configuration.
     */
    public function getServiceConfig(): array
    {
        return [
            'name' => 'SHTI',
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'health_status' => $this->checkHealth(),
        ];
    }
}