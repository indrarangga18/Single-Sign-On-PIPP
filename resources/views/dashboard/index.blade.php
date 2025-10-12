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

@push('styles')
<style>
    /* Sidebar minimal */
    .dashboard-container { display: flex; gap: 20px; }
    .sidebar { width: 220px; min-width: 220px; background: rgba(13,23,56,0.6); border: 1px solid rgba(120,200,255,0.28); border-radius: 12px; backdrop-filter: blur(10px); padding: 12px; position: sticky; top: 20px; height: calc(100vh - 40px); }
    .sidebar .brand { display: flex; align-items: center; gap: 10px; padding: 8px 6px; border-bottom: 1px solid rgba(120,200,255,0.18); margin-bottom: 10px; }
    .sidebar .brand .logo { width: 28px; height: 28px; border-radius: 8px; background: linear-gradient(135deg,#6366f1,#06b6d4); }
    .sidebar .brand .label { display: flex; flex-direction: column; line-height: 1.2; }
    .sidebar .brand .subtitle { color: #a7b4c7; font-size: 12px; }
    .sidebar .menu { display: flex; flex-direction: column; gap: 8px; }
    .sidebar .menu a { display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 10px; color: #dbeafe; text-decoration: none; border: 1px solid rgba(120,200,255,0.18); background: rgba(10,20,50,0.35); }
    .sidebar .menu a.active { border-color: rgba(140,220,255,0.35); }
    .page { flex: 1; min-width: 0; }
    /* Header brand dengan logo KKP PIPP */
    .header-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 0; }
    .brand-logo { height: clamp(36px, 5vw, 56px); width: auto; padding: 0; background: transparent; box-shadow: none; border-radius: 0; transition: transform .2s ease; display: block; }
    .header-brand:hover .brand-logo { transform: translateY(-1px) scale(1.02); }
    .brand-text .brand-title { font-size: 20px; font-weight: 700; background: linear-gradient(90deg, #06b6d4, #8b5cf6); -webkit-background-clip: text; background-clip: text; color: transparent; letter-spacing: 0.4px; }
    .brand-text .brand-subtitle { color: #a7b4c7; font-size: 12px; }
    .topbar { background: rgba(18,28,56,0.65); border: 1px solid rgba(120,200,255,0.25); border-radius: 12px; backdrop-filter: blur(10px); padding: 10px 12px; margin-bottom: 16px; box-shadow: 0 0 20px rgba(34,211,238,0.15), 0 0 40px rgba(168,85,247,0.12); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 99; }
    .top-actions { display: flex; align-items: center; gap: 10px; margin-left: auto; }
    .action-btn { background: transparent; border: 1px solid rgba(120,200,255,0.28); color: #a7b4c7; border-radius: 10px; padding: 8px 10px; cursor: pointer; transition: all .2s ease; }
    .action-btn:hover { color: #e6f2ff; border-color: rgba(140,220,255,0.45); transform: translateY(-1px); }
    .action-avatar { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #06b6d4, #8b5cf6); color: white; display:flex; align-items:center; justify-content:center; font-weight:600; }
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <aside class="sidebar" id="sidebar">
        <div class="brand"><div class="logo"></div><div class="label"><div class="title">{{ $user->full_name ?? $user->username }}</div><div class="subtitle">{{ $user->getRoleNames()->first() ?? 'Pengguna' }}</div></div><button class="toggle-btn" id="toggleSidebar" aria-label="Toggle Sidebar">◀</button></div>
        <nav class="menu">
            <a href="{{ route('dashboard') }}" class="active">🏠 Dashboard</a>
            <a href="{{ route('services.spb') }}">📄 SPB</a>
            <a href="{{ route('services.shti') }}">🧮 SHTI</a>
            <a href="{{ route('services.sahbandar') }}">🚢 Sahbandar</a>
            <a href="{{ route('services.epit') }}">🖥️ EPIT</a>
        </nav>
    </aside>
    <main class="page">
    <!-- Topbar Brand di paling atas -->
    <div class="topbar">
        <div class="header-brand">
            <img src="{{ asset('images/logo-kkp-pipp.png') }}" class="brand-logo" alt="KKP PIPP Logo">
            <div class="brand-text">
                <div class="brand-title">SSO PIPP</div>
                <div class="brand-subtitle">Single Sign-On KKP</div>
            </div>
        </div>
        <div class="top-actions">
            <button class="action-btn" aria-label="Notifikasi">🔔 Notifikasi</button>
            <div class="action-avatar" title="Profil">{{ strtoupper(substr($user->full_name ?? $user->username, 0, 1)) }}</div>
        </div>
    </div>
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
    </main>
</div>
@endsection

@push('styles')
<style>
    :root {
        --neon-bg: radial-gradient(1200px 600px at 10% 10%, rgba(0,255,255,0.08), transparent 60%),
                    radial-gradient(1000px 500px at 90% 20%, rgba(168,85,247,0.12), transparent 55%),
                    linear-gradient(135deg, #0b1020, #0d1b2a 60%, #0b1020);
        --glass-bg: rgba(16, 24, 48, 0.55);
        --glass-border: rgba(120, 200, 255, 0.25);
        --text-primary: #e6f2ff;
        --text-muted: #a7b4c7;
        --neon-primary: #22d3ee; /* cyan */
        --neon-secondary: #a855f7; /* purple */
        --neon-success: #22c55e;
        --shadow-neon: 0 0 20px rgba(34, 211, 238, 0.15), 0 0 40px rgba(168, 85, 247, 0.12);
    }
    .dashboard-container { background: var(--neon-bg); }
    .sidebar { background: var(--glass-bg); border: 1px solid var(--glass-border); box-shadow: var(--shadow-neon); }
    .sidebar .brand { color: var(--text-primary); }
    .sidebar .brand .title { font-weight: 600; letter-spacing: 0.3px; }
    .sidebar .toggle-btn { margin-left: auto; background: transparent; border: 1px solid var(--glass-border); color: var(--text-muted); border-radius: 8px; padding: 6px 8px; cursor: pointer; transition: all .25s ease; }
    .sidebar .toggle-btn:hover { color: var(--text-primary); border-color: rgba(120,200,255,0.45); transform: translateY(-1px); }
    .sidebar.collapsed { width: 64px; min-width: 64px; }
    .sidebar.collapsed .brand .label { display: none; }
    .sidebar.collapsed .menu a span { display: none; }
    .sidebar.collapsed .menu a { justify-content: center; padding: 10px; }
    .sidebar.collapsed .toggle-btn { font-size: 0; width: 28px; height: 28px; }
    .sidebar .menu a { background: rgba(20,30,60,0.45); border-color: var(--glass-border); }
    .sidebar .menu a.active { border-color: rgba(140,220,255,0.35); box-shadow: 0 0 12px rgba(34,211,238,0.12); }

    .dashboard-header { background: rgba(18, 28, 56, 0.55); border: 1px solid var(--glass-border); color: var(--text-primary); box-shadow: var(--shadow-neon); }
    .welcome-text h1 { color: var(--text-primary); }
    .welcome-text p { color: var(--text-muted); }
    .user-avatar { background: linear-gradient(135deg, var(--neon-primary), var(--neon-secondary)); box-shadow: 0 6px 18px rgba(34, 211, 238, 0.25); }
    .logout-btn { background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 6px 16px rgba(239,68,68,0.25); }

    .stat-card { background: rgba(18,28,56,0.55); border: 1px solid var(--glass-border); color: var(--text-primary); box-shadow: var(--shadow-neon); }
    .stat-icon { background: linear-gradient(135deg, var(--neon-success), var(--neon-primary)); box-shadow: 0 6px 16px rgba(34, 197, 94, 0.25); }
    .stat-number { color: var(--text-primary); }
    .stat-label { color: var(--text-muted); }

    .services-section, .activities-section { background: rgba(18,28,56,0.55); border: 1px solid var(--glass-border); color: var(--text-primary); box-shadow: var(--shadow-neon); }
    .section-title { color: var(--text-primary); }
    .service-card { background: rgba(12,20,40,0.55); border-color: var(--glass-border); color: var(--text-primary); box-shadow: 0 6px 16px rgba(168,85,247,0.12); }
    .service-card:hover { border-color: rgba(140,220,255,0.45); box-shadow: 0 12px 28px rgba(34,211,238,0.18); }
    .service-icon { background: linear-gradient(135deg, var(--neon-primary), var(--neon-secondary)); }
    .service-name { color: var(--text-primary); }
    .service-description { color: var(--text-muted); }

    .activity-item { border-bottom-color: rgba(120,200,255,0.18); }
    .activity-icon { background: rgba(10,20,44,0.55); color: var(--text-primary); border: 1px solid var(--glass-border); }
    .activity-description { color: var(--text-primary); }
    .activity-time { color: var(--text-muted); }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hover micro-interactions
    const cards = document.querySelectorAll('.stat-card, .service-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Sidebar collapse toggle with persistence
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    const collapsedKey = 'sso-sidebar-collapsed';

    const applyCollapsed = () => {
        const isCollapsed = localStorage.getItem(collapsedKey) === 'true';
        sidebar.classList.toggle('collapsed', isCollapsed);
        if (toggleBtn) toggleBtn.textContent = isCollapsed ? '▶' : '◀';
    };
    applyCollapsed();

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const next = !(localStorage.getItem(collapsedKey) === 'true');
            localStorage.setItem(collapsedKey, String(next));
            applyCollapsed();
        });
    }

    // Auto-refresh stats every 5 minutes (placeholder)
    setInterval(function() { console.log('Stats refresh interval'); }, 300000);
});
</script>
@endpush