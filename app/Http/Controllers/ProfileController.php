<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the profile settings form.
     */
    public function edit(): View
    {
        $user = Auth::user();
        return view('profile.settings', compact('user'));
    }

    /**
     * Handle profile update.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'username'    => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'email'       => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'first_name'  => ['required', 'string', 'max:255'],
            'last_name'   => ['required', 'string', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:30'],
            'photo'       => ['nullable', 'image', 'max:2048'],
        ]);

        $oldValues = [
            'username'   => $user->username,
            'email'      => $user->email,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'phone'      => $user->phone,
            'avatar_path'=> $user->preferences['avatar_path'] ?? null,
        ];

        $user->username   = $validated['username'];
        $user->email      = $validated['email'];
        $user->first_name = $validated['first_name'];
        $user->last_name  = $validated['last_name'];
        $user->phone      = $validated['phone'] ?? null;

        // Handle photo upload if provided
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('avatars', 'public');
            $prefs = $user->preferences ?? [];
            $prefs['avatar_path'] = $path;
            $user->preferences = $prefs;
        }

        $user->save();

        AuditLog::createLog([
            'user_id'   => $user->id,
            'action'    => 'profile_update',
            'resource'  => 'user_profile',
            'severity'  => 'info',
            'old_values'=> $oldValues,
            'new_values'=> [
                'username'   => $user->username,
                'email'      => $user->email,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'phone'      => $user->phone,
                'avatar_path'=> $user->preferences['avatar_path'] ?? null,
            ],
            'description' => 'User updated profile settings',
        ]);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}