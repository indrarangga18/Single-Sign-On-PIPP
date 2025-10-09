<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;
use App\Services\Auth\AuthService;

class ServiceAccessMiddleware
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $service): mixed
    {
        $user = Auth::user();

        if (!$user) {
            return $this->forbiddenResponse('Authentication required');
        }

        // Check if user can access the service
        if (!$this->authService->canAccessService($user, $service)) {
            // Log unauthorized access attempt
            AuditLog::logSecurityEvent(
                $user,
                'unauthorized_service_access_attempt',
                $request->ip(),
                $request->userAgent(),
                [
                    'service' => $service,
                    'user_roles' => $user->roles->pluck('name')->toArray(),
                    'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray()
                ]
            );

            return $this->forbiddenResponse("Access denied to {$service} service");
        }

        // Add service info to request
        $request->merge([
            'accessed_service' => $service,
            'user_service_permissions' => $this->authService->getUserServicePermissions($user, $service)
        ]);

        return $next($request);
    }

    /**
     * Return forbidden response.
     */
    private function forbiddenResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'SERVICE_ACCESS_DENIED'
        ], 403);
    }
}