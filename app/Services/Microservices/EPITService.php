<?php

namespace App\Services\Microservices;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EPITService
{
    protected $baseUrl;
    protected $apiKey;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.epit.base_url', 'http://epit-service:8080/api');
        $this->apiKey = config('services.epit.api_key');
        $this->timeout = config('services.epit.timeout', 30);
    }

    /**
     * Get port information systems.
     */
    public function getPortSystems(array $params = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl . '/port-systems', $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('EPIT API error: ' . $response->body());
            throw new \Exception('Failed to fetch port systems from EPIT service');

        } catch (\Exception $e) {
            Log::error('EPIT service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get specific port system.
     */
    public function getPortSystem(string $systemId): ?array
    {
        try {
            $cacheKey = "epit_port_system_{$systemId}";
            
            return Cache::remember($cacheKey, 600, function () use ($systemId) {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . "/port-systems/{$systemId}");

                if ($response->successful()) {
                    return $response->json();
                }

                if ($response->status() === 404) {
                    return null;
                }

                Log::error('EPIT API error: ' . $response->body());
                throw new \Exception('Failed to fetch port system details');
            });

        } catch (\Exception $e) {
            Log::error('EPIT service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get vessel tracking data.
     */
    public function getVesselTracking(array $params = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl . '/vessel-tracking', $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('EPIT API error: ' . $response->body());
            throw new \Exception('Failed to fetch vessel tracking data');

        } catch (\Exception $e) {
            Log::error('EPIT service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get port operations data.
     */
    public function getPortOperations(array $params = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl . '/port-operations', $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('EPIT API error: ' . $response->body());
            throw new \Exception('Failed to fetch port operations data');

        } catch (\Exception $e) {
            Log::error('EPIT service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create new port operation.
     */
    public function createPortOperation(array $operationData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/port-operations', $operationData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('EPIT API error: ' . $response->body());
            throw new \Exception('Failed to create port operation');

        } catch (\Exception $e) {
            Log::error('EPIT service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update port operation status.
     */
    public function updateOperationStatus(string $operationId, array $updateData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->put($this->baseUrl . "/port-operations/{$operationId}/status", $updateData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('EPIT API error: ' . $response->body());
            throw new \Exception('Failed to update port operation status');

        } catch (\Exception $e) {
            Log::error('EPIT service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get berth availability.
     */
    public function getBerthAvailability(array $params = []): array
    {
        try {
            $cacheKey = 'epit_berth_availability_' . md5(serialize($params));
            
            return Cache::remember($cacheKey, 300, function () use ($params) {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . '/berth-availability', $params);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('EPIT API error: ' . $response->body());
                throw new \Exception('Failed to fetch berth availability');
            });

        } catch (\Exception $e) {
            Log::error('EPIT service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get cargo statistics.
     */
    public function getCargoStatistics(array $params = []): array
    {
        try {
            $cacheKey = 'epit_cargo_statistics_' . md5(serialize($params));
            
            return Cache::remember($cacheKey, 900, function () use ($params) {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . '/cargo-statistics', $params);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('EPIT API error: ' . $response->body());
                throw new \Exception('Failed to fetch cargo statistics');
            });

        } catch (\Exception $e) {
            Log::error('EPIT service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get port performance metrics.
     */
    public function getPortPerformance(array $params = []): array
    {
        try {
            $cacheKey = 'epit_port_performance_' . md5(serialize($params));
            
            return Cache::remember($cacheKey, 1800, function () use ($params) {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . '/port-performance', $params);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('EPIT API error: ' . $response->body());
                throw new \Exception('Failed to fetch port performance metrics');
            });

        } catch (\Exception $e) {
            Log::error('EPIT service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get weather and sea conditions.
     */
    public function getWeatherConditions(array $params = []): array
    {
        try {
            $cacheKey = 'epit_weather_conditions_' . md5(serialize($params));
            
            return Cache::remember($cacheKey, 600, function () use ($params) {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . '/weather-conditions', $params);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('EPIT API error: ' . $response->body());
                throw new \Exception('Failed to fetch weather conditions');
            });

        } catch (\Exception $e) {
            Log::error('EPIT service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get port facilities information.
     */
    public function getPortFacilities(array $params = []): array
    {
        try {
            $cacheKey = 'epit_port_facilities_' . md5(serialize($params));
            
            return Cache::remember($cacheKey, 3600, function () use ($params) {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . '/port-facilities', $params);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('EPIT API error: ' . $response->body());
                throw new \Exception('Failed to fetch port facilities');
            });

        } catch (\Exception $e) {
            Log::error('EPIT service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get vessel schedules.
     */
    public function getVesselSchedules(array $params = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl . '/vessel-schedules', $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('EPIT API error: ' . $response->body());
            throw new \Exception('Failed to fetch vessel schedules');

        } catch (\Exception $e) {
            Log::error('EPIT service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get dashboard statistics.
     */
    public function getDashboardStats(): array
    {
        try {
            $cacheKey = 'epit_dashboard_stats';
            
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

                Log::error('EPIT API error: ' . $response->body());
                throw new \Exception('Failed to fetch dashboard statistics from EPIT service');
            });

        } catch (\Exception $e) {
            Log::error('EPIT service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate EPIT report.
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

            Log::error('EPIT API error: ' . $response->body());
            throw new \Exception('Failed to generate EPIT report');

        } catch (\Exception $e) {
            Log::error('EPIT service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync data with EPIT service.
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
                Cache::forget('epit_dashboard_stats');
                
                return $response->json();
            }

            Log::error('EPIT API error: ' . $response->body());
            throw new \Exception('Failed to sync data with EPIT service');

        } catch (\Exception $e) {
            Log::error('EPIT service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get real-time port status.
     */
    public function getPortStatus(string $portCode = null): array
    {
        try {
            $cacheKey = 'epit_port_status_' . ($portCode ?? 'all');
            
            return Cache::remember($cacheKey, 60, function () use ($portCode) {
                $url = $this->baseUrl . '/port-status';
                if ($portCode) {
                    $url .= "/{$portCode}";
                }

                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($url);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('EPIT API error: ' . $response->body());
                throw new \Exception('Failed to fetch port status');
            });

        } catch (\Exception $e) {
            Log::error('EPIT service error: ' . $e->getMessage());
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
            Log::error('EPIT health check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get service configuration.
     */
    public function getServiceConfig(): array
    {
        return [
            'name' => 'EPIT',
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'health_status' => $this->checkHealth(),
        ];
    }
}