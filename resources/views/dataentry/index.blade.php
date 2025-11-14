@extends('layouts.app')

@push('styles')
<style>
    .dashboard-container { display: flex; gap: 20px; min-height: 100vh; background: radial-gradient(1200px 600px at 10% 10%, rgba(0,255,255,0.08), transparent 60%), radial-gradient(1000px 500px at 90% 20%, rgba(168,85,247,0.12), transparent 55%), linear-gradient(135deg, #0b1020, #0d1b2a 60%, #0b1020); padding: 20px; }
    .sidebar { width: 220px; min-width: 220px; background: rgba(16, 24, 48, 0.55); border: 1px solid rgba(120, 200, 255, 0.25); border-radius: 12px; backdrop-filter: blur(10px); padding: 12px; position: sticky; top: 20px; height: calc(100vh - 40px); box-shadow: 0 0 20px rgba(34, 211, 238, 0.15), 0 0 40px rgba(168, 85, 247, 0.12); }
    .sidebar .brand { display: flex; align-items: center; gap: 10px; padding: 8px 6px; border-bottom: 1px solid rgba(120,200,255,0.18); margin-bottom: 10px; color: #e6f2ff; }
    .sidebar .brand .logo { width: 28px; height: 28px; border-radius: 8px; background: linear-gradient(135deg,#6366f1,#06b6d4); }
    .sidebar .brand .label { display: flex; flex-direction: column; line-height: 1.2; }
    .sidebar .brand .title { font-weight: 600; letter-spacing: 0.3px; }
    .sidebar .brand .subtitle { color: #a7b4c7; font-size: 12px; }
    .sidebar .toggle-btn { margin-left: auto; background: transparent; border: 1px solid rgba(120,200,255,0.25); color: #a7b4c7; border-radius: 8px; padding: 6px 8px; cursor: pointer; transition: all .25s ease; }
    .sidebar .toggle-btn:hover { color: #e6f2ff; border-color: rgba(140,220,255,0.45); transform: translateY(-1px); }
    .sidebar.collapsed { width: 64px; min-width: 64px; }
    .sidebar.collapsed .brand .label { display: none; }
    .sidebar .menu { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
    .sidebar .menu a { display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 10px; color: #dbeafe; text-decoration: none; border: 1px solid rgba(120,200,255,0.18); background: rgba(10,20,50,0.35); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; position: relative; }
    .sidebar .menu a.active { border-color: rgba(140,220,255,0.35); box-shadow: 0 0 12px rgba(34,211,238,0.12); }
    .page { flex: 1; min-width: 0; }
    .topbar { background: rgba(18,28,56,0.65); border: 1px solid rgba(120,200,255,0.25); border-radius: 12px; backdrop-filter: blur(10px); padding: 10px 12px; margin-bottom: 16px; box-shadow: 0 0 20px rgba(34,211,238,0.15), 0 0 40px rgba(168,85,247,0.12); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 99; color: #e6f2ff; }
    .header-brand { display: flex; align-items: center; gap: 12px; }
    .brand-logo { height: clamp(36px, 5vw, 56px); width: auto; transition: transform .2s ease; }
    .header-brand:hover .brand-logo { transform: translateY(-1px) scale(1.02); }
    .brand-text .brand-title { font-size: 20px; font-weight: 700; background: linear-gradient(90deg, #06b6d4, #8b5cf6); -webkit-background-clip: text; background-clip: text; color: transparent; letter-spacing: 0.4px; }
    .brand-text .brand-subtitle { color: #a7b4c7; font-size: 12px; }
    .top-actions { display: flex; align-items: center; gap: 10px; }
    .action-btn { background: transparent; border: 1px solid rgba(120,200,255,0.28); color: #a7b4c7; border-radius: 10px; padding: 8px 10px; cursor: pointer; transition: all .2s ease; }
    .action-btn:hover { color: #e6f2ff; border-color: rgba(140,220,255,0.45); transform: translateY(-1px); }
    .action-avatar { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #06b6d4, #8b5cf6); color: white; display:flex; align-items:center; justify-content:center; font-weight:600; }
    .profile-menu { position: relative; display: flex; align-items: center; gap: 6px; }
    .avatar-btn { border: none; background: linear-gradient(135deg, #06b6d4, #8b5cf6); color: white; width: 32px; height: 32px; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-weight:600; cursor: pointer; }
    .caret-btn { width: 28px; height: 28px; border-radius: 8px; border: 1px solid rgba(120,200,255,0.28); background: transparent; color: #a7b4c7; display:flex; align-items:center; justify-content:center; cursor: pointer; }
    .caret-btn:hover { color: #e6f2ff; border-color: rgba(140,220,255,0.45); }
    .profile-dropdown { position: absolute; right: 0; top: calc(100% + 8px); min-width: 180px; background: rgba(18,28,56,0.75); border: 1px solid rgba(120,200,255,0.28); border-radius: 10px; box-shadow: 0 12px 28px rgba(34,211,238,0.18); padding: 8px; display: none; z-index: 100; backdrop-filter: blur(10px); }
    .profile-menu.open .profile-dropdown { display: block; }
    .profile-dropdown a { display: block; padding: 8px 10px; color: #e6f2ff; text-decoration: none; border-radius: 8px; }
    .profile-dropdown a:hover { background: rgba(10,20,50,0.35); }

    .section-title { color: #e6f2ff; font-size: 24px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .services-section { background: rgba(18,28,56,0.55); border: 1px solid rgba(120,200,255,0.25); border-radius: 15px; padding: 30px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
    .placeholder { border: 1px dashed rgba(120,200,255,0.35); border-radius: 12px; padding: 18px; color: #a7b4c7; text-align: center; }

    .sidebar.collapsed .menu a { justify-content: center; padding: 10px; }
    .sidebar .menu a::before { content: ''; display: none; }
    .sidebar.collapsed .menu a::before { display: inline-block; width: 18px; height: 18px; background-repeat: no-repeat; background-size: contain; opacity: 0.85; filter: drop-shadow(0 0 6px rgba(34,211,238,0.6)) drop-shadow(0 0 10px rgba(168,85,247,0.5)); }
    .sidebar .menu a[data-icon="dashboard"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><rect x=%223%22 y=%223%22 width=%227%22 height=%227%22/><rect x=%2214%22 y=%223%22 width=%227%22 height=%227%22/><rect x=%223%22 y=%2214%22 width=%227%22 height=%227%22/><rect x=%2214%22 y=%2214%22 width=%227%22 height=%227%22/></svg>'); }
    .sidebar .menu a[data-icon="spb"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><path d=%22M7 3h9l4 4v14H7z%22/><path d=%22M16 3v5h5%22/></svg>'); }
    .sidebar .menu a[data-icon="shti"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><rect x=%223%22 y=%223%22 width=%2218%22 height=%2218%22 rx=%222%22/><path d=%22M12 6v12%22/><path d=%22M6 12h12%22/></svg>'); }
    .sidebar .menu a[data-icon="sahbandar"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><path d=%22M3 14l9-4 9 4%22/><path d=%22M5 18h14%22/></svg>'); }
    .sidebar .menu a[data-icon="epit"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><rect x=%223%22 y=%224%22 width=%2218%22 height=%2212%22 rx=%222%22/><path d=%22M12 16v4%22/><path d=%22M8 20h8%22/></svg>'); }
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="logo"></div>
            <div class="label">
                <div class="title">{{ (auth()->user()->full_name ?? auth()->user()->username) ?? 'Pengguna' }}</div>
                <div class="subtitle">{{ auth()->user()->getRoleNames()->first() ?? 'Pengguna' }}</div>
            </div>
            <button class="toggle-btn" id="toggleSidebar" aria-label="Toggle Sidebar">◀</button>
        </div>
        <nav class="menu">
            <a href="{{ route('dashboard') }}" data-icon="dashboard"><span>Dashboard</span></a>
            <a href="{{ route('dataentry') }}" class="active" data-icon="spb"><span>Data Entry</span></a>
            <a href="{{ route('pemantauan') }}" data-icon="shti"><span>Pemantauan</span></a>
            <a href="{{ route('services.sahbandar') }}" data-icon="sahbandar"><span>Sahbandar</span></a>
            <a href="{{ route('services.epit') }}" data-icon="epit"><span>EPIT</span></a>
        </nav>
    </aside>
    <main class="page">
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
                <div class="profile-menu">
                    <button class="action-avatar avatar-btn" id="avatarMenuBtn" aria-label="Profil">{{ strtoupper(substr((auth()->user()->full_name ?? auth()->user()->username) ?? 'P', 0, 1)) }}</button>
                    <button class="caret-btn" id="profileCaretBtn" aria-label="Buka menu profil" aria-haspopup="true" aria-expanded="false">▾</button>
                    <div class="profile-dropdown" role="menu" aria-label="Menu Profil">
                        <a href="{{ route('profile.settings') }}" role="menuitem">Pengaturan Profil</a>
                        <form method="POST" action="{{ route('logout') }}" style="margin:0;" role="none">
                            @csrf
                            <button type="submit" class="dropdown-link" role="menuitem">Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="services-section">
            <h2 class="section-title">📝 Data Entry</h2>
            <div class="placeholder">Form entri dan pengelolaan data akan ditambahkan di sini.</div>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar collapse toggle with persistence
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    const collapsedKey = 'sso-sidebar-collapsed';
    const applyCollapsed = () => {
        const isCollapsed = localStorage.getItem(collapsedKey) === 'true';
        sidebar.classList.toggle('collapsed', isCollapsed);
        if (toggleBtn) toggleBtn.textContent = isCollapsed ? '☰' : '◀';
    };
    applyCollapsed();
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const next = !(localStorage.getItem(collapsedKey) === 'true');
            localStorage.setItem(collapsedKey, String(next));
            applyCollapsed();
        });
    }

    // Profile dropdown toggle
    const profileMenu = document.querySelector('.profile-menu');
    const caretBtn = document.getElementById('profileCaretBtn');
    if (profileMenu && caretBtn) {
        const closeMenu = () => {
            profileMenu.classList.remove('open');
            caretBtn.setAttribute('aria-expanded', 'false');
        };
        const openMenu = () => {
            profileMenu.classList.add('open');
            caretBtn.setAttribute('aria-expanded', 'true');
        };
        caretBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            profileMenu.classList.contains('open') ? closeMenu() : openMenu();
        });
        document.addEventListener('click', (e) => {
            if (!profileMenu.contains(e.target)) closeMenu();
        });
    }
});
</script>
@endpush