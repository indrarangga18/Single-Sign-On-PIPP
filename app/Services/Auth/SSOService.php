<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\SSOSession;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class SSOService
{
    /**
     * Generate service-specific token for SSO.
     */
    public function generateServiceToken(User $user, string $service, SSOSession $session): string
    {
        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'aud' => $service,
            'iat' => now()->timestamp,
            'exp' => $session->expires_at->timestamp,
            'session_id' => $session->session_id,
            'user_data' => [
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'roles' => $user->roles->pluck('name')->toArray(),
                'permissions' => $this->getServicePermissions($user, $service),
            ]
        ];

        // Create JWT token with service-specific claims
        $token = JWTAuth::claims($payload)->fromUser($user);

        // Cache token for quick validation
        $cacheKey = "sso_token:{$service}:{$session->session_id}";
        Cache::put($cacheKey, [
            'token' => $token,
            'user_id' => $user->id,
            'session_id' => $session->session_id,
            'expires_at' => $session->expires_at
        ], $session->expires_at);

        return $token;
    }

    /**
     * Validate service token.
     */
    public function validateServiceToken(string $token, string $service): array
    {
        try {
            // Set and parse the token
            JWTAuth::setToken($token);
            $payload = JWTAuth::getPayload();

            // Verify audience (service)
            if ($payload->get('aud') !== $service) {
                return [
                    'valid' => false,
                    'message' => 'Token not valid for this service',
                    'user' => null,
                    'session' => null
                ];
            }

            // Get user and session
            $userId = $payload->get('sub');
            $sessionId = $payload->get('session_id');

            $user = User::find($userId);
            if (!$user || $user->status !== 'active') {
                return [
                    'valid' => false,
                    'message' => 'User not found or inactive',
                    'user' => null,
                    'session' => null
                ];
            }

            $session = SSOSession::where('session_id', $sessionId)
                ->where('user_id', $userId)
                ->where('service_name', $service)
                ->first();

            if (!$session || !$session->isActive()) {
                return [
                    'valid' => false,
                    'message' => 'Session not found or expired',
                    'user' => null,
                    'session' => null
                ];
            }

            return [
                'valid' => true,
                'message' => 'Token is valid',
                'user' => $user,
                'session' => $session
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Token validation failed: ' . $e->getMessage(),
                'user' => null,
                'session' => null
            ];
        }
    }

    /**
     * Get user permissions for specific service.
     */
    protected function getServicePermissions(User $user, string $service): array
    {
        return $user->getAllPermissions()
            ->filter(function ($permission) use ($service) {
                return str_contains($permission->name, $service) || 
                       str_contains($permission->name, 'manage') ||
                       str_contains($permission->name, 'admin');
            })
            ->pluck('name')
            ->toArray();
    }

    /**
     * Notify service about logout.
     */
    public function notifyServiceLogout(SSOSession $session): void
    {
        try {
            $serviceConfig = config("services.{$session->service_name}");
            
            if (!$serviceConfig || !isset($serviceConfig['logout_callback'])) {
                return;
            }

            $callbackUrl = $serviceConfig['logout_callback'];
            
            Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $serviceConfig['api_key'],
                    'Content-Type' => 'application/json',
                ])
                ->post($callbackUrl, [
                    'session_id' => $session->session_id,
                    'user_id' => $session->user_id,
                    'action' => 'logout',
                    'timestamp' => now()->toISOString(),
                ]);

        } catch (\Exception $e) {
            // Log the error but don't fail the logout process
            AuditLog::createLog([
                'user_id' => $session->user_id,
                'action' => 'logout_notification_failed',
                'service_name' => $session->service_name,
                'severity' => 'error',
                'description' => "Failed to notify {$session->service_name} about logout: {$e->getMessage()}",
            ]);
        }
    }

    /**
     * Validate service callback.
     */
    public function validateServiceCallback(string $service, array $data): array
    {
        $serviceConfig = config("services.{$service}");
        
        if (!$serviceConfig) {
            return [
                'valid' => false,
                'message' => 'Service not configured'
            ];
        }

        // Validate required fields
        $requiredFields = ['session_id', 'user_id', 'timestamp'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return [
                    'valid' => false,
                    'message' => "Missing required field: {$field}"
                ];
            }
        }

        // Validate session
        $session = SSOSession::where('session_id', $data['session_id'])
            ->where('user_id', $data['user_id'])
            ->where('service_name', $service)
            ->first();

        if (!$session) {
            return [
                'valid' => false,
                'message' => 'Session not found'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Callback is valid',
            'session' => $session
        ];
    }

    /**
     * Get service configuration.
     */
    public function getServiceConfig(string $service): ?array
    {
        return config("services.{$service}");
    }

    /**
     * Check service availability.
     */
    public function checkServiceAvailability(string $service): array
    {
        try {
            $serviceConfig = $this->getServiceConfig($service);
            
            if (!$serviceConfig) {
                return [
                    'available' => false,
                    'message' => 'Service not configured'
                ];
            }

            $healthUrl = $serviceConfig['url'] . '/health';
            
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $serviceConfig['api_key'],
                ])
                ->get($healthUrl);

            if ($response->successful()) {
                return [
                    'available' => true,
                    'message' => 'Service is available',
                    'response_time' => $response->transferStats?->getTransferTime()
                ];
            }

            return [
                'available' => false,
                'message' => 'Service returned error: ' . $response->status()
            ];

        } catch (\Exception $e) {
            return [
                'available' => false,
                'message' => 'Service check failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Clean up expired sessions.
     */
    public function cleanupExpiredSessions(): int
    {
        $expiredSessions = SSOSession::expired()->get();
        $count = $expiredSessions->count();

        foreach ($expiredSessions as $session) {
            $session->markAsExpired();
            
            // Clear cached tokens
            $cacheKey = "sso_token:{$session->service_name}:{$session->session_id}";
            Cache::forget($cacheKey);
        }

        if ($count > 0) {
            AuditLog::createLog([
                'action' => 'cleanup_expired_sessions',
                'severity' => 'info',
                'description' => "Cleaned up {$count} expired SSO sessions",
            ]);
        }

        return $count;
    }

    /**
     * Get SSO statistics.
     */
    public function getStatistics(): array
    {
        $totalSessions = SSOSession::count();
        $activeSessions = SSOSession::active()->count();
        $expiredSessions = SSOSession::expired()->count();

        $serviceStats = SSOSession::selectRaw('service_name, COUNT(*) as total, 
                                              SUM(CASE WHEN status = "active" AND expires_at > NOW() THEN 1 ELSE 0 END) as active')
            ->groupBy('service_name')
            ->get()
            ->keyBy('service_name');

        $recentLogins = AuditLog::where('action', 'login')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return [
            'total_sessions' => $totalSessions,
            'active_sessions' => $activeSessions,
            'expired_sessions' => $expiredSessions,
            'service_statistics' => $serviceStats,
            'recent_logins_7days' => $recentLogins,
            'session_success_rate' => $totalSessions > 0 ? round(($activeSessions / $totalSessions) * 100, 2) : 0,
        ];
    }

    /**
     * Extend session expiration.
     */
    public function extendSession(string $sessionId, int $minutes = null): array
    {
        try {
            $session = SSOSession::where('session_id', $sessionId)->first();
            
            if (!$session) {
                return [
                    'success' => false,
                    'message' => 'Session not found'
                ];
            }

            if (!$session->isActive()) {
                return [
                    'success' => false,
                    'message' => 'Session is not active'
                ];
            }

            $minutes = $minutes ?? config('services.sso.session_lifetime', 480);
            $session->extend($minutes);

            // Update cached token expiration
            $cacheKey = "sso_token:{$session->service_name}:{$session->session_id}";
            if (Cache::has($cacheKey)) {
                $cachedData = Cache::get($cacheKey);
                $cachedData['expires_at'] = $session->expires_at;
                Cache::put($cacheKey, $cachedData, $session->expires_at);
            }

            return [
                'success' => true,
                'message' => 'Session extended successfully',
                'expires_at' => $session->expires_at
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to extend session: ' . $e->getMessage()
            ];
        }
    }
}