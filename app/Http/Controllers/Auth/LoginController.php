<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Show the application's login form.
     */
    public function showLoginForm(Request $request): View
    {
        // Check if coming from SSO flow
        $service = $request->query('service');
        $redirectUri = $request->query('redirect_uri');
        $state = $request->query('state');

        return view('auth.login', compact('service', 'redirectUri', 'state'));
    }

    /**
     * Handle a login request to the application.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('username', 'password');
        $remember = $request->boolean('remember');

        // Try to find user by username or email
        $user = User::where('username', $credentials['username'])
                   ->orWhere('email', $credentials['username'])
                   ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            // Log failed login attempt
            AuditLog::logSecurityEvent(
                'failed_login',
                "Failed login attempt for username: {$credentials['username']}",
                'warning'
            );

            return back()->withErrors([
                'username' => 'Username atau password yang Anda masukkan salah.',
            ])->onlyInput('username');
        }

        // Check if user is active
        if ($user->status !== 'active') {
            return back()->withErrors([
                'username' => 'Akun Anda tidak aktif. Silakan hubungi administrator.',
            ])->onlyInput('username');
        }

        // Log the user in
        Auth::login($user, $remember);

        // Update last login information
        $user->updateLastLogin();

        // Log successful login
        AuditLog::logLogin($user);

        // Check if this is part of SSO flow
        if (session()->has('sso_service')) {
            $service = session('sso_service');
            $redirectUri = session('sso_redirect_uri');
            $state = session('sso_state');

            // Clear SSO session data
            session()->forget(['sso_service', 'sso_redirect_uri', 'sso_state']);

            // Handle SSO callback
            return redirect()->route('sso.callback', [
                'service' => $service,
                'redirect_uri' => $redirectUri,
                'state' => $state
            ]);
        }

        // Regular login - redirect to dashboard
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            // Log logout
            AuditLog::logLogout($user);

            // Revoke active SSO sessions
            $user->ssoSessions()->active()->update(['status' => 'revoked']);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}