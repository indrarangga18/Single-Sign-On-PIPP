<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Auth\SSOService;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class SSOTokenMiddleware
{
    protected $ssoService;

    public function __construct(SSOService $ssoService)
    {
        $this->ssoService = $ssoService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $service = null): mixed
    {
        try {
            // Get token from header
            $token = $request->header('X-SSO-Token') ?? $request->bearerToken();
            
            if (!$token) {
                return $this->unauthorizedResponse('SSO token is required');
            }

            // Validate the token
            $validation = $this->ssoService->validateServiceToken($token, $service);
            
            if (!$validation['valid']) {
                // Log failed validation attempt
                AuditLog::logSecurityEvent(
                    null,
                    'sso_token_validation_failed',
                    $request->ip(),
                    $request->userAgent(),
                    [
                        'service' => $service,
                        'token_prefix' => substr($token, 0, 10) . '...',
                        'reason' => $validation['reason'] ?? 'Invalid token'
                    ]
                );

                return $this->unauthorizedResponse($validation['reason'] ?? 'Invalid SSO token');
            }

            // Add user and service info to request
            $request->merge([
                'sso_user' => $validation['user'],
                'sso_service' => $service,
                'sso_session_id' => $validation['session_id'] ?? null,
            ]);

            // Log successful SSO access
            if (isset($validation['user'])) {
                AuditLog::logServiceAccess(
                    $validation['user'],
                    $service ?? 'unknown',
                    'sso_token_access',
                    [
                        'session_id' => $validation['session_id'] ?? null,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ]
                );
            }

            return $next($request);

        } catch (\Exception $e) {
            Log::error('SSO Token Middleware Error: ' . $e->getMessage(), [
                'service' => $service,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->unauthorizedResponse('SSO token validation failed');
        }
    }

    /**
     * Return unauthorized response.
     */
    private function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'SSO_TOKEN_INVALID'
        ], 401);
    }
}