<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SSOSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'user_id',
        'service_name',
        'service_url',
        'client_ip',
        'user_agent',
        'session_data',
        'last_activity',
        'expires_at',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'session_data' => 'array',
        'last_activity' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the SSO session.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active sessions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope a query to only include expired sessions.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
                    ->orWhere('status', 'expired');
    }

    /**
     * Scope a query to filter by service.
     */
    public function scopeByService($query, $service)
    {
        return $query->where('service_name', $service);
    }

    /**
     * Check if the session is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at > now();
    }

    /**
     * Mark the session as expired.
     */
    public function markAsExpired()
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Mark the session as revoked.
     */
    public function revoke()
    {
        $this->update(['status' => 'revoked']);
    }

    /**
     * Update last activity.
     */
    public function updateActivity()
    {
        $this->update(['last_activity' => now()]);
    }

    /**
     * Extend session expiration.
     */
    public function extend($minutes = null)
    {
        $minutes = $minutes ?? config('services.sso.session_lifetime', 480);
        $this->update([
            'expires_at' => now()->addMinutes($minutes),
            'last_activity' => now(),
        ]);
    }
}