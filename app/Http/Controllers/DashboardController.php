<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use App\Models\SSOSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        // Get user statistics
        $stats = [
            'total_logins' => AuditLog::where('user_id', $user->id)
                ->where('action', 'login')
                ->count(),
            'active_sessions' => SSOSession::where('user_id', $user->id)
                ->where('status', 'active')
                ->count(),
            'last_login' => $user->last_login_at,
            'services_accessed' => AuditLog::where('user_id', $user->id)
                ->where('action', 'service_access')
                ->distinct('service_name')
                ->count(),
        ];

        // Get recent activities
        $recentActivities = AuditLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get available services
        $services = [
            [
                'name' => 'SAHBANDAR',
                'description' => 'Sistem Administrasi Pelabuhan',
                'url' => route('services.sahbandar'),
                'icon' => 'ship',
                'status' => 'active'
            ],
            [
                'name' => 'SPB',
                'description' => 'Sistem Perizinan Berusaha',
                'url' => route('services.spb'),
                'icon' => 'document',
                'status' => 'active'
            ],
            [
                'name' => 'SHTI',
                'description' => 'Sistem Harmonisasi Tarif Indonesia',
                'url' => route('services.shti'),
                'icon' => 'calculator',
                'status' => 'active'
            ],
            [
                'name' => 'EPIT',
                'description' => 'Electronic Port Information Technology',
                'url' => route('services.epit'),
                'icon' => 'server',
                'status' => 'active'
            ],
        ];

        return view('dashboard.index', compact('user', 'stats', 'recentActivities', 'services'));
    }
}