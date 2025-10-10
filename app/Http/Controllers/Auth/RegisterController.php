<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show the application's registration form.
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request for the application.
     */
    public function register(RegisterRequest $request): RedirectResponse
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

            // Log the user in
            Auth::login($user);

            // Update last login information
            $user->updateLastLogin();

            return redirect()->route('dashboard')->with('success', 'Registrasi berhasil! Selamat datang di sistem SSO PIPP.');
            
        } catch (\Exception $e) {
            return back()->withErrors([
                'email' => 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.',
            ])->withInput($request->except('password', 'password_confirmation'));
        }
    }
}