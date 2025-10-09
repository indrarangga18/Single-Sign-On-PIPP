<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SSOSession;
use App\Models\AuditLog;
use App\Services\Auth\SSOService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class SSOController extends Controller
{
    protected $ssoService;

    public function __construct(SSOService $ssoService)
    {
        $this->ssoService = $ssoService;
    }

    /**
     * Initiate SSO login process.
     */
    public function login(Request $request)
    {
        $request->validate([
            'service' => 'required|string|in:sahbandar,spb,shti,epit',
            'redirect_uri' => 'required|url',
        ]);

        try {
            $service = $request->input('service');
            $redirectUri = $request->input('redirect_uri');
            
            // Generate state parameter for security
            $state = Str::random(40);
            session(['sso_state' => $state, 'sso_service' => $service, 'sso_redirect_uri' => $redirectUri]);

            // Check if user is already authenticated
            if (Auth::check()) {
                return $this->handleAuthenticatedUser(Auth::user(), $service, $redirectUri, $state);
            }

            // Redirect to login page with service context
            return redirect()->route('login', [
                'service' => $service,
                'redirect_uri' => $redirectUri,
                'state' => $state
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'SSO login initiation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle SSO callback after authentication.
     */
    public function callback(Request $request)
    {
        $request->validate([
            'state' => 'required|string',
        ]);

        try {
            // Verify state parameter
            if ($request->input('state') !== session('sso_state')) {
                throw new \Exception('Invalid state parameter');
            }

            $user = Auth::user();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            $service = session('sso_service');
            $redirectUri = session('sso_redirect_uri');

            // Clear session data
            session()->forget(['sso_state', 'sso_service', 'sso_redirect_uri']);

            return $this->handleAuthenticatedUser($user, $service, $redirectUri);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'SSO callback failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle authenticated user for SSO.
     */
    protected function handleAuthenticatedUser($user, $service, $redirectUri, $state = null)
    {
        try {
            // Check if user has access to the requested service
            if (!$user->hasServiceAccess($service)) {
                AuditLog::logSecurityEvent(
                    'unauthorized_service_access',
                    "User {$user->username} attempted to access {$service} without permission",
                    'warning',
                    $user
                );
                
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to the requested service'
                ], 403);
            }

            // Create SSO session
            $ssoSession = $this->createSSOSession($user, $service, $redirectUri);
            
            // Generate service token
            $serviceToken = $this->ssoService->generateServiceToken($user, $service, $ssoSession);

            // Log service access
            AuditLog::logServiceAccess($user, $service);

            // Build redirect URL with token
            $redirectUrl = $redirectUri . '?' . http_build_query([
                'token' => $serviceToken,
                'session_id' => $ssoSession->session_id,
                'state' => $state
            ]);

            return redirect($redirectUrl);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'SSO authentication failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate SSO token.
     */
    public function validateToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'service' => 'required|string',
        ]);

        try {
            $token = $request->input('token');
            $service = $request->input('service');

            $validation = $this->ssoService->validateServiceToken($token, $service);

            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token',
                    'valid' => false
                ], 401);
            }

            $user = $validation['user'];
            $ssoSession = $validation['session'];

            // Update session activity
            $ssoSession->updateActivity();

            return response()->json([
                'success' => true,
                'valid' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'full_name' => $user->full_name,
                        'roles' => $user->roles->pluck('name'),
                        'permissions' => $user->getAllPermissions()->pluck('name'),
                    ],
                    'session' => [
                        'id' => $ssoSession->session_id,
                        'service' => $ssoSession->service_name,
                        'expires_at' => $ssoSession->expires_at,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token validation failed',
                'error' => $e->getMessage(),
                'valid' => false
            ], 500);
        }
    }

    /**
     * SSO logout from all services.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user) {
                // Revoke all active SSO sessions
                $activeSessions = $user->getActiveSSOSessions();
                
                foreach ($activeSessions as $session) {
                    $session->revoke();
                    
                    // Notify service about logout (if callback URL is configured)
                    $this->ssoService->notifyServiceLogout($session);
                }

                // Log SSO logout
                AuditLog::logLogout($user, 'SSO');
            }

            // Logout from main application
            Auth::logout();
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out from all services'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'SSO logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's active SSO sessions.
     */
    public function getSessions(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $sessions = $user->getActiveSSOSessions();

            return response()->json([
                'success' => true,
                'data' => $sessions->map(function ($session) {
                    return [
                        'session_id' => $session->session_id,
                        'service_name' => $session->service_name,
                        'service_url' => $session->service_url,
                        'last_activity' => $session->last_activity,
                        'expires_at' => $session->expires_at,
                        'client_ip' => $session->client_ip,
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get SSO sessions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke specific SSO session.
     */
    public function revokeSession(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        try {
            $user = Auth::user();
            $sessionId = $request->input('session_id');

            $session = $user->ssoSessions()
                ->where('session_id', $sessionId)
                ->where('status', 'active')
                ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found or already revoked'
                ], 404);
            }

            $session->revoke();

            // Notify service about session revocation
            $this->ssoService->notifyServiceLogout($session);

            // Log session revocation
            AuditLog::createLog([
                'user_id' => $user->id,
                'action' => 'revoke_session',
                'service_name' => $session->service_name,
                'severity' => 'info',
                'description' => "User {$user->username} revoked SSO session for {$session->service_name}",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Session revoked successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create SSO session.
     */
    protected function createSSOSession($user, $service, $redirectUri)
    {
        $sessionId = Str::uuid();
        $expiresAt = now()->addMinutes(config('services.sso.session_lifetime', 480));

        return SSOSession::create([
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'service_name' => $service,
            'service_url' => $redirectUri,
            'client_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_data' => [
                'login_time' => now()->toISOString(),
                'user_roles' => $user->roles->pluck('name')->toArray(),
            ],
            'last_activity' => now(),
            'expires_at' => $expiresAt,
            'status' => 'active',
        ]);
    }
}