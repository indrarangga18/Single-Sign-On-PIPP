<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    /**
     * Authenticate user with credentials.
     */
    public function authenticate(array $credentials): array
    {
        $user = User::where('username', $credentials['username'])
                   ->orWhere('email', $credentials['username'])
                   ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
                'user' => null,
                'token' => null
            ];
        }

        if ($user->status !== 'active') {
            return [
                'success' => false,
                'message' => 'Account is not active',
                'user' => null,
                'token' => null
            ];
        }

        $token = JWTAuth::fromUser($user);
        $user->updateLastLogin();

        return [
            'success' => true,
            'message' => 'Authentication successful',
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * Create new user account.
     */
    public function createUser(array $userData): User
    {
        $userData['password'] = Hash::make($userData['password']);
        $userData['status'] = 'active';

        $user = User::create($userData);
        
        // Assign default role based on department
        $this->assignDefaultRole($user, $userData['department']);

        // Log user creation
        AuditLog::createLog([
            'user_id' => $user->id,
            'action' => 'create_user',
            'severity' => 'info',
            'description' => "New user created: {$user->username} in {$userData['department']} department",
        ]);

        return $user;
    }

    /**
     * Assign default role based on department.
     */
    protected function assignDefaultRole(User $user, string $department): void
    {
        $roleMapping = [
            'sahbandar' => 'sahbandar',
            'spb' => 'spb-officer',
            'shti' => 'shti-officer',
            'epit' => 'epit-officer',
            'admin' => 'admin',
        ];

        $role = $roleMapping[$department] ?? 'user';
        $user->assignRole($role);
    }

    /**
     * Change user password.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): array
    {
        if (!Hash::check($currentPassword, $user->password)) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect'
            ];
        }

        $user->update(['password' => Hash::make($newPassword)]);

        // Log password change
        AuditLog::createLog([
            'user_id' => $user->id,
            'action' => 'change_password',
            'severity' => 'info',
            'description' => "User {$user->username} changed password",
        ]);

        return [
            'success' => true,
            'message' => 'Password changed successfully'
        ];
    }

    /**
     * Update user profile.
     */
    public function updateProfile(User $user, array $data): User
    {
        $oldValues = $user->only(array_keys($data));
        $user->update($data);

        // Log profile update
        AuditLog::createLog([
            'user_id' => $user->id,
            'action' => 'update_profile',
            'old_values' => $oldValues,
            'new_values' => $data,
            'severity' => 'info',
            'description' => "User {$user->username} updated profile",
        ]);

        return $user->fresh();
    }

    /**
     * Deactivate user account.
     */
    public function deactivateUser(User $user): void
    {
        $user->update(['status' => 'inactive']);

        // Revoke all active SSO sessions
        $user->ssoSessions()->active()->update(['status' => 'revoked']);

        // Log user deactivation
        AuditLog::createLog([
            'user_id' => $user->id,
            'action' => 'deactivate_user',
            'severity' => 'warning',
            'description' => "User {$user->username} account deactivated",
        ]);
    }

    /**
     * Activate user account.
     */
    public function activateUser(User $user): void
    {
        $user->update(['status' => 'active']);

        // Log user activation
        AuditLog::createLog([
            'user_id' => $user->id,
            'action' => 'activate_user',
            'severity' => 'info',
            'description' => "User {$user->username} account activated",
        ]);
    }

    /**
     * Get user permissions for a specific service.
     */
    public function getUserServicePermissions(User $user, string $service): array
    {
        $servicePermissions = $user->getAllPermissions()
            ->filter(function ($permission) use ($service) {
                return str_contains($permission->name, $service);
            })
            ->pluck('name')
            ->toArray();

        return $servicePermissions;
    }

    /**
     * Check if user can access service.
     */
    public function canAccessService(User $user, string $service): bool
    {
        return $user->hasPermissionTo("access {$service}") || 
               $user->hasPermissionTo("manage {$service}") ||
               $user->hasRole('super-admin');
    }

    /**
     * Get user dashboard data.
     */
    public function getUserDashboardData(User $user): array
    {
        $activeSessions = $user->getActiveSSOSessions();
        $recentLogs = $user->auditLogs()
            ->latest()
            ->limit(10)
            ->get();

        $accessibleServices = [];
        $services = ['sahbandar', 'spb', 'shti', 'epit'];
        
        foreach ($services as $service) {
            if ($this->canAccessService($user, $service)) {
                $accessibleServices[] = $service;
            }
        }

        return [
            'user' => $user->load('roles', 'permissions'),
            'active_sessions' => $activeSessions,
            'recent_activities' => $recentLogs,
            'accessible_services' => $accessibleServices,
            'session_count' => $activeSessions->count(),
        ];
    }

    /**
     * Validate JWT token.
     */
    public function validateToken(string $token): array
    {
        try {
            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();

            if (!$user) {
                return [
                    'valid' => false,
                    'message' => 'Invalid token',
                    'user' => null
                ];
            }

            if ($user->status !== 'active') {
                return [
                    'valid' => false,
                    'message' => 'User account is not active',
                    'user' => null
                ];
            }

            return [
                'valid' => true,
                'message' => 'Token is valid',
                'user' => $user
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Token validation failed: ' . $e->getMessage(),
                'user' => null
            ];
        }
    }

    /**
     * Refresh JWT token.
     */
    public function refreshToken(): array
    {
        try {
            $newToken = JWTAuth::refresh();
            $user = JWTAuth::setToken($newToken)->toUser();

            return [
                'success' => true,
                'token' => $newToken,
                'user' => $user
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Token refresh failed: ' . $e->getMessage(),
                'token' => null,
                'user' => null
            ];
        }
    }
}