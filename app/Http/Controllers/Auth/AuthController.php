<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Handle user login.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->only('username', 'password');
            
            // Attempt to authenticate user
            if (!$token = auth('api')->attempt($credentials)) {
                AuditLog::logSecurityEvent(
                    'failed_login',
                    "Failed login attempt for username: {$request->username}",
                    'warning'
                );
                
                throw ValidationException::withMessages([
                    'username' => ['The provided credentials are incorrect.'],
                ]);
            }

            $user = auth('api')->user();
            
            // Check if user is active
            if ($user->status !== 'active') {
                auth('api')->logout();
                throw ValidationException::withMessages([
                    'username' => ['Your account is not active.'],
                ]);
            }

            // Update last login information
            $user->updateLastLogin();
            
            // Log successful login
            AuditLog::logLogin($user);

            return $this->respondWithToken($token, $user);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle user registration.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $userData = $request->validated();
            $userData['password'] = Hash::make($userData['password']);
            $userData['status'] = 'active';

            $user = User::create($userData);
            
            // Assign default role
            $user->assignRole('user');

            // Log user registration
            AuditLog::createLog([
                'user_id' => $user->id,
                'action' => 'register',
                'severity' => 'info',
                'description' => "New user registered: {$user->username}",
            ]);

            $token = auth('api')->login($user);

            return $this->respondWithToken($token, $user, 'User registered successfully', 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the authenticated user.
     */
    public function me(): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $user->load('roles', 'permissions');

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'roles' => $user->roles->pluck('name'),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(): JsonResponse
    {
        try {
            $user = auth('api')->user();
            
            // Log logout
            AuditLog::logLogout($user);
            
            // Revoke active SSO sessions
            $user->ssoSessions()->active()->update(['status' => 'revoked']);
            
            auth('api')->logout();

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh a token.
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = auth('api')->refresh();
            $user = auth('api')->user();

            return $this->respondWithToken($token, $user, 'Token refreshed successfully');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change user password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $user = auth('api')->user();

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 422);
            }

            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            // Log password change
            AuditLog::createLog([
                'user_id' => $user->id,
                'action' => 'change_password',
                'severity' => 'info',
                'description' => "User {$user->username} changed password",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Password change failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken($token, $user, $message = 'Login successful', $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'full_name' => $user->full_name,
                    'roles' => $user->roles->pluck('name'),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ]
            ]
        ], $status);
    }
}