@extends('layouts.app')

@push('styles')
<style>
    .dashboard-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 20px;
    }
    
    .dashboard-header {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .welcome-section {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .welcome-text h1 {
        color: #1e40af;
        font-size: 28px;
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .welcome-text p {
        color: #64748b;
        font-size: 16px;
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .user-avatar {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #3b82f6, #1e40af);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        font-weight: 600;
    }
    
    .logout-btn {
        background: #ef4444;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .logout-btn:hover {
        background: #dc2626;
        transform: translateY(-2px);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        margin: 0 auto 15px;
        background: linear-gradient(135deg, #10b981, #059669);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
    }
    
    .stat-number {
        font-size: 32px;
        font-weight: 700;
        color: #1e40af;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #64748b;
        font-size: 14px;
        font-weight: 500;
    }
    
    .services-section {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .section-title {
        color: #1e40af;
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .service-card {
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 25px;
        text-decoration: none;
        transition: all 0.3s ease;
        display: block;
    }
    
    .service-card:hover {
        border-color: #3b82f6;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15);
    }
    
    .service-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .service-icon {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #3b82f6, #1e40af);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
    }
    
    .service-name {
        color: #1e40af;
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }
    
    .service-description {
        color: #64748b;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .activities-section {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .activity-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-icon {
        width: 35px;
        height: 35px;
        background: #f3f4f6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        font-size: 14px;
    }
    
    .activity-content {
        flex: 1;
    }
    
    .activity-description {
        color: #374151;
        font-size: 14px;
        margin-bottom: 2px;
    }
    
    .activity-time {
        color: #9ca3af;
        font-size: 12px;
    }
    
    @media (max-width: 768px) {
        .welcome-section {
            flex-direction: column;
            text-align: center;
        }
        
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }
        
        .services-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <!-- Header Section -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <div class="welcome-text">
                <h1>Selamat Datang, {{ $user->full_name ?? $user->username }}!</h1>
                <p>Sistem Single Sign-On Kementerian Kelautan dan Perikanan</p>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    {{ strtoupper(substr($user->full_name ?? $user->username, 0, 1)) }}
                </div>
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="logout-btn">Keluar</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Statistics Section -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-number">{{ $stats['total_logins'] }}</div>
            <div class="stat-label">Total Login</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🔐</div>
            <div class="stat-number">{{ $stats['active_sessions'] }}</div>
            <div class="stat-label">Sesi Aktif</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🚀</div>
            <div class="stat-number">{{ $stats['services_accessed'] }}</div>
            <div class="stat-label">Layanan Diakses</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⏰</div>
            <div class="stat-number">{{ $stats['last_login'] ? $stats['last_login']->format('H:i') : '-' }}</div>
            <div class="stat-label">Login Terakhir</div>
        </div>
    </div>

    <!-- Services Section -->
    <div class="services-section">
        <h2 class="section-title">
            🌐 Layanan Tersedia
        </h2>
        <div class="services-grid">
            @foreach($services as $service)
            <a href="{{ $service['url'] }}" class="service-card">
                <div class="service-header">
                    <div class="service-icon">
                        @if($service['icon'] === 'ship')
                            🚢
                        @elseif($service['icon'] === 'document')
                            📄
                        @elseif($service['icon'] === 'calculator')
                            🧮
                        @elseif($service['icon'] === 'server')
                            🖥️
                        @endif
                    </div>
                    <h3 class="service-name">{{ $service['name'] }}</h3>
                </div>
                <p class="service-description">{{ $service['description'] }}</p>
            </a>
            @endforeach
        </div>
    </div>

    <!-- Recent Activities Section -->
    <div class="activities-section">
        <h2 class="section-title">
            📋 Aktivitas Terbaru
        </h2>
        @if($recentActivities->count() > 0)
            @foreach($recentActivities as $activity)
            <div class="activity-item">
                <div class="activity-icon">
                    @if($activity->action === 'login')
                        🔑
                    @elseif($activity->action === 'logout')
                        🚪
                    @elseif($activity->action === 'service_access')
                        🌐
                    @else
                        📝
                    @endif
                </div>
                <div class="activity-content">
                    <div class="activity-description">{{ $activity->description }}</div>
                    <div class="activity-time">{{ $activity->created_at->diffForHumans() }}</div>
                </div>
            </div>
            @endforeach
        @else
            <div class="activity-item">
                <div class="activity-icon">ℹ️</div>
                <div class="activity-content">
                    <div class="activity-description">Belum ada aktivitas yang tercatat</div>
                    <div class="activity-time">Mulai gunakan layanan untuk melihat aktivitas</div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add some interactive effects
    const cards = document.querySelectorAll('.stat-card, .service-card');
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Auto-refresh stats every 5 minutes
    setInterval(function() {
        // You can implement AJAX refresh here if needed
        console.log('Stats refresh interval');
    }, 300000);
});
</script>
@endpush