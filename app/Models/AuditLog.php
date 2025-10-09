<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'resource',
        'service_name',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'session_id',
        'severity',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Get the user that performed the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to filter by action.
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to filter by service.
     */
    public function scopeByService($query, $service)
    {
        return $query->where('service_name', $service);
    }

    /**
     * Scope a query to filter by severity.
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Create a new audit log entry.
     */
    public static function createLog(array $data)
    {
        return static::create(array_merge([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
        ], $data));
    }

    /**
     * Log user login.
     */
    public static function logLogin($user, $service = null)
    {
        return static::createLog([
            'user_id' => $user->id,
            'action' => 'login',
            'service_name' => $service,
            'severity' => 'info',
            'description' => "User {$user->username} logged in" . ($service ? " to {$service}" : ''),
        ]);
    }

    /**
     * Log user logout.
     */
    public static function logLogout($user, $service = null)
    {
        return static::createLog([
            'user_id' => $user->id,
            'action' => 'logout',
            'service_name' => $service,
            'severity' => 'info',
            'description' => "User {$user->username} logged out" . ($service ? " from {$service}" : ''),
        ]);
    }

    /**
     * Log service access.
     */
    public static function logServiceAccess($user, $service, $resource = null)
    {
        return static::createLog([
            'user_id' => $user->id,
            'action' => 'access_service',
            'resource' => $resource,
            'service_name' => $service,
            'severity' => 'info',
            'description' => "User {$user->username} accessed {$service}" . ($resource ? " - {$resource}" : ''),
        ]);
    }

    /**
     * Log security event.
     */
    public static function logSecurityEvent($action, $description, $severity = 'warning', $user = null)
    {
        return static::createLog([
            'user_id' => $user?->id,
            'action' => $action,
            'severity' => $severity,
            'description' => $description,
        ]);
    }
}