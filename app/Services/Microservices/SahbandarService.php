<?php

namespace App\Services\Microservices;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SahbandarService
{
    protected $baseUrl;
    protected $apiKey;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.sahbandar.base_url', 'http://sahbandar-service:8080/api');
        $this->apiKey = config('services.sahbandar.api_key');
        $this->timeout = config('services.sahbandar.timeout', 30);
    }

    /**
     * Get vessel data from Sahbandar service.
     */
    public function getVessels(array $params = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl . '/vessels', $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Sahbandar API error: ' . $response->body());
            throw new \Exception('Failed to fetch vessels from Sahbandar service');

        } catch (\Exception $e) {
            Log::error('Sahbandar service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get specific vessel details.
     */
    public function getVessel(string $vesselId): ?array
    {
        try {
            $cacheKey = "sahbandar_vessel_{$vesselId}";
            
            return Cache::remember($cacheKey, 300, function () use ($vesselId) {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . "/vessels/{$vesselId}");

                if ($response->successful()) {
                    return $response->json();
                }

                if ($response->status() === 404) {
                    return null;
                }

                Log::error('Sahbandar API error: ' . $response->body());
                throw new \Exception('Failed to fetch vessel details from Sahbandar service');
            });

        } catch (\Exception $e) {
            Log::error('Sahbandar service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get port information.
     */
    public function getPorts(array $params = []): array
    {
        try {
            $cacheKey = 'sahbandar_ports_' . md5(serialize($params));
            
            return Cache::remember($cacheKey, 600, function () use ($params) {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . '/ports', $params);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('Sahbandar API error: ' . $response->body());
                throw new \Exception('Failed to fetch ports from Sahbandar service');
            });

        } catch (\Exception $e) {
            Log::error('Sahbandar service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get port activities.
     */
    public function getPortActivities(array $params = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl . '/port-activities', $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Sahbandar API error: ' . $response->body());
            throw new \Exception('Failed to fetch port activities from Sahbandar service');

        } catch (\Exception $e) {
            Log::error('Sahbandar service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create vessel clearance.
     */
    public function createVesselClearance(array $clearanceData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/vessel-clearances', $clearanceData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Sahbandar API error: ' . $response->body());
            throw new \Exception('Failed to create vessel clearance');

        } catch (\Exception $e) {
            Log::error('Sahbandar service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update vessel clearance.
     */
    public function updateVesselClearance(string $clearanceId, array $updateData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->put($this->baseUrl . "/vessel-clearances/{$clearanceId}", $updateData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Sahbandar API error: ' . $response->body());
            throw new \Exception('Failed to update vessel clearance');

        } catch (\Exception $e) {
            Log::error('Sahbandar service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get clearance history.
     */
    public function getClearanceHistory(array $params = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl . '/vessel-clearances', $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Sahbandar API error: ' . $response->body());
            throw new \Exception('Failed to fetch clearance history from Sahbandar service');

        } catch (\Exception $e) {
            Log::error('Sahbandar service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get dashboard statistics.
     */
    public function getDashboardStats(): array
    {
        try {
            $cacheKey = 'sahbandar_dashboard_stats';
            
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

                Log::error('Sahbandar API error: ' . $response->body());
                throw new \Exception('Failed to fetch dashboard statistics from Sahbandar service');
            });

        } catch (\Exception $e) {
            Log::error('Sahbandar service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync data with Sahbandar service.
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
                Cache::forget('sahbandar_dashboard_stats');
                Cache::flush(); // Consider more selective cache clearing
                
                return $response->json();
            }

            Log::error('Sahbandar API error: ' . $response->body());
            throw new \Exception('Failed to sync data with Sahbandar service');

        } catch (\Exception $e) {
            Log::error('Sahbandar service error: ' . $e->getMessage());
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
            Log::error('Sahbandar health check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get service configuration.
     */
    public function getServiceConfig(): array
    {
        return [
            'name' => 'Sahbandar',
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'health_status' => $this->checkHealth(),
        ];
    }
}