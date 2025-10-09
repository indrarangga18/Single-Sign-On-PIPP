<?php

namespace App\Services\Microservices;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SPBService
{
    protected $baseUrl;
    protected $apiKey;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.spb.base_url', 'http://spb-service:8080/api');
        $this->apiKey = config('services.spb.api_key');
        $this->timeout = config('services.spb.timeout', 30);
    }

    /**
     * Get SPB applications.
     */
    public function getApplications(array $params = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl . '/applications', $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('SPB API error: ' . $response->body());
            throw new \Exception('Failed to fetch SPB applications');

        } catch (\Exception $e) {
            Log::error('SPB service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get specific SPB application.
     */
    public function getApplication(string $applicationId): ?array
    {
        try {
            $cacheKey = "spb_application_{$applicationId}";
            
            return Cache::remember($cacheKey, 300, function () use ($applicationId) {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . "/applications/{$applicationId}");

                if ($response->successful()) {
                    return $response->json();
                }

                if ($response->status() === 404) {
                    return null;
                }

                Log::error('SPB API error: ' . $response->body());
                throw new \Exception('Failed to fetch SPB application details');
            });

        } catch (\Exception $e) {
            Log::error('SPB service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create new SPB application.
     */
    public function createApplication(array $applicationData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/applications', $applicationData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('SPB API error: ' . $response->body());
            throw new \Exception('Failed to create SPB application');

        } catch (\Exception $e) {
            Log::error('SPB service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update SPB application status.
     */
    public function updateApplicationStatus(string $applicationId, array $updateData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->put($this->baseUrl . "/applications/{$applicationId}/status", $updateData);

            if ($response->successful()) {
                // Clear cache for this application
                Cache::forget("spb_application_{$applicationId}");
                
                return $response->json();
            }

            Log::error('SPB API error: ' . $response->body());
            throw new \Exception('Failed to update SPB application status');

        } catch (\Exception $e) {
            Log::error('SPB service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Issue SPB certificate.
     */
    public function issueCertificate(string $applicationId, array $certificateData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . "/applications/{$applicationId}/certificate", $certificateData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('SPB API error: ' . $response->body());
            throw new \Exception('Failed to issue SPB certificate');

        } catch (\Exception $e) {
            Log::error('SPB service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get SPB certificates.
     */
    public function getCertificates(array $params = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl . '/certificates', $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('SPB API error: ' . $response->body());
            throw new \Exception('Failed to fetch SPB certificates');

        } catch (\Exception $e) {
            Log::error('SPB service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify SPB certificate.
     */
    public function verifyCertificate(string $certificateNumber): array
    {
        try {
            $cacheKey = "spb_certificate_verify_{$certificateNumber}";
            
            return Cache::remember($cacheKey, 600, function () use ($certificateNumber) {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl . "/certificates/{$certificateNumber}/verify");

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('SPB API error: ' . $response->body());
                throw new \Exception('Failed to verify SPB certificate');
            });

        } catch (\Exception $e) {
            Log::error('SPB service error: ' . $e->getMessage());
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

            Log::error('SPB API error: ' . $response->body());
            throw new \Exception('Failed to fetch vessel tracking data');

        } catch (\Exception $e) {
            Log::error('SPB service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get dashboard statistics.
     */
    public function getDashboardStats(): array
    {
        try {
            $cacheKey = 'spb_dashboard_stats';
            
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

                Log::error('SPB API error: ' . $response->body());
                throw new \Exception('Failed to fetch dashboard statistics from SPB service');
            });

        } catch (\Exception $e) {
            Log::error('SPB service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate SPB report.
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

            Log::error('SPB API error: ' . $response->body());
            throw new \Exception('Failed to generate SPB report');

        } catch (\Exception $e) {
            Log::error('SPB service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync data with SPB service.
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
                Cache::forget('spb_dashboard_stats');
                
                return $response->json();
            }

            Log::error('SPB API error: ' . $response->body());
            throw new \Exception('Failed to sync data with SPB service');

        } catch (\Exception $e) {
            Log::error('SPB service error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get vessel information.
     */
    public function getVesselInfo(string $vesselId): ?array
    {
        try {
            $cacheKey = "spb_vessel_{$vesselId}";
            
            return Cache::remember($cacheKey, 600, function () use ($vesselId) {
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

                Log::error('SPB API error: ' . $response->body());
                throw new \Exception('Failed to fetch vessel information');
            });

        } catch (\Exception $e) {
            Log::error('SPB service error: ' . $e->getMessage());
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
            Log::error('SPB health check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get service configuration.
     */
    public function getServiceConfig(): array
    {
        return [
            'name' => 'SPB',
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'health_status' => $this->checkHealth(),
        ];
    }
}