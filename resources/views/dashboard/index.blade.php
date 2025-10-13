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
    .sidebar .menu a { display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 10px; color: #dbeafe; text-decoration: none; border: 1px solid rgba(120,200,255,0.18); background: rgba(10,20,50,0.35); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; position: relative; }
    .sidebar .menu a::before { content: ''; display: none; }
    /* Ikon outline transparan bergaya lingkaran + simbol, tampil saat collapsed */
    .sidebar .menu a[data-icon="dashboard"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><circle cx=%2212%22 cy=%2212%22 r=%229%22/><rect x=%226.5%22 y=%226.5%22 width=%224.5%22 height=%224.5%22/><rect x=%2213%22 y=%226.5%22 width=%224.5%22 height=%224.5%22/><rect x=%226.5%22 y=%2213%22 width=%224.5%22 height=%224.5%22/><rect x=%2213%22 y=%2213%22 width=%224.5%22 height=%224.5%22/></svg>'); }
    .sidebar .menu a[data-icon="spb"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><circle cx=%2212%22 cy=%2212%22 r=%229%22/><path d=%22M8 7h6l3 3v7H8z%22/><path d=%22M14 7v3h3%22/></svg>'); }
    .sidebar .menu a[data-icon="shti"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><circle cx=%2212%22 cy=%2212%22 r=%229%22/><circle cx=%228%22 cy=%2210%22 r=%221.25%22/><circle cx=%2212%22 cy=%2210%22 r=%221.25%22/><circle cx=%2216%22 cy=%2210%22 r=%221.25%22/><circle cx=%228%22 cy=%2214%22 r=%221.25%22/><circle cx=%2212%22 cy=%2214%22 r=%221.25%22/><circle cx=%2216%22 cy=%2214%22 r=%221.25%22/></svg>'); }
    .sidebar .menu a[data-icon="sahbandar"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><circle cx=%2212%22 cy=%2212%22 r=%229%22/><path d=%22M5 13l7-3 7 3%22/><path d=%22M7 16h10%22/></svg>'); }
    .sidebar .menu a[data-icon="epit"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><circle cx=%2212%22 cy=%2212%22 r=%229%22/><rect x=%227%22 y=%229%22 width=%2210%22 height=%227%22 rx=%221.5%22/><path d=%22M12 16v3%22/><path d=%22M9 19h6%22/></svg>'); }
    .sidebar .menu a[data-icon="vtc"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><circle cx=%2212%22 cy=%2212%22 r=%229%22/><rect x=%227%22 y=%228%22 width=%228%22 height=%228%22 rx=%221.5%22/><polygon points=%2216,10 20,12 16,14%22/></svg>'); }
    .sidebar .menu a[data-icon="cctv"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><circle cx=%2212%22 cy=%2212%22 r=%229%22/><rect x=%227%22 y=%209%22 width=%2210%22 height=%225%22 rx=%221%22/><circle cx=%2210%22 cy=%2011.5%22 r=%221.2%22/></svg>'); }
    .sidebar .menu a[data-icon="profil"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><circle cx=%2212%22 cy=%2210%22 r=%223.5%22/><path d=%22M4.5 19c2.5-2.5 12.5-2.5 15 0%22/></svg>'); }
    .sidebar .menu a[data-icon="kinerja"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><circle cx=%2212%22 cy=%2212%22 r=%229%22/><rect x=%229%22 y=%2212%22 width=%223%22 height=%224%22/><rect x=%2012.5%22 y=%2010%22 width=%203%22 height=%206%22/><rect x=%2016%22 y=%208%22 width=%203%22 height=%208%22/></svg>'); }
    .sidebar .menu a[data-icon="kapal"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><circle cx=%2212%22 cy=%2212%22 r=%229%22/><path d=%22M6 13l6-3 6 3%22/><path d=%22M8 16h8%22/></svg>'); }
    .sidebar .menu a[data-icon="pendaratan"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><circle cx=%2212%22 cy=%2212%22 r=%229%22/><path d=%22M6 12c4-6 8-6 12 0-4 6-8 6-12 0%22/></svg>'); }
    .sidebar .menu a[data-icon="fasilitas"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><circle cx=%2212%22 cy=%2212%22 r=%229%22/><path d=%22M8 12l3 3 5-5%22/></svg>'); }
    .sidebar .menu a[data-icon="pnbp_pasca"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><circle cx=%2212%22 cy=%2212%22 r=%229%22/><path d=%22M8 12h8%22/></svg>'); }
    .sidebar .menu a[data-icon="pnbp_non_sda"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><circle cx=%2212%22 cy=%2212%22 r=%229%22/><path d=%22M9 11h6M9 13h6%22/></svg>'); }
    .sidebar .menu a[data-icon="evkin"]::before { background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23e6f2ff%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><circle cx=%2212%22 cy=%2212%22 r=%229%22/><path d=%22M9 12l2 2 4-4%22/></svg>'); }
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
    /* Dropdown menu profil di header */
    .profile-menu { position: relative; display: flex; align-items: center; gap: 6px; }
    .avatar-btn { border: none; background: linear-gradient(135deg, #06b6d4, #8b5cf6); color: white; width: 32px; height: 32px; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-weight:600; cursor: pointer; }
    .caret-btn { width: 28px; height: 28px; border-radius: 8px; border: 1px solid rgba(120,200,255,0.28); background: transparent; color: #a7b4c7; display:flex; align-items:center; justify-content:center; cursor: pointer; }
    .caret-btn:hover { color: #e6f2ff; border-color: rgba(140,220,255,0.45); }
    .profile-dropdown { position: absolute; right: 0; top: calc(100% + 8px); min-width: 180px; background: rgba(18,28,56,0.75); border: 1px solid rgba(120,200,255,0.28); border-radius: 10px; box-shadow: 0 12px 28px rgba(34,211,238,0.18); padding: 8px; display: none; z-index: 100; backdrop-filter: blur(10px); }
    .profile-menu.open .profile-dropdown { display: block; }
    .profile-dropdown a { display: block; padding: 8px 10px; color: #e6f2ff; text-decoration: none; border-radius: 8px; }
    .profile-dropdown a:hover { background: rgba(10,20,50,0.35); }
    .profile-dropdown .dropdown-link { display: block; width: 100%; text-align: left; padding: 8px 10px; color: #e6f2ff; background: transparent; border: none; border-radius: 8px; cursor: pointer; }
    .profile-dropdown .dropdown-link:hover { background: rgba(10,20,50,0.35); }
    /* Animasi & tema neon futuristik untuk kartu layanan */
    @keyframes glowPulse {
        0% { box-shadow: 0 0 10px rgba(34,211,238,0.25), 0 0 18px rgba(168,85,247,0.20); transform: translateY(0); }
        50% { box-shadow: 0 0 18px rgba(34,211,238,0.35), 0 0 28px rgba(168,85,247,0.30); transform: translateY(-2px); }
        100% { box-shadow: 0 0 10px rgba(34,211,238,0.25), 0 0 18px rgba(168,85,247,0.20); transform: translateY(0); }
    }
    .glow-animated { animation: glowPulse 3s ease-in-out infinite; }
    .service-card { background: linear-gradient(135deg, rgba(16,24,48,0.65), rgba(24,32,64,0.65)); border: 1px solid rgba(120,200,255,0.28); border-radius: 12px; padding: 25px; text-decoration: none; transition: all 0.25s ease; display: block; box-shadow: 0 10px 24px rgba(34,211,238,0.12), 0 0 24px rgba(168,85,247,0.10); }
    .service-card:hover { border-color: rgba(140,220,255,0.45); transform: translateY(-3px); box-shadow: 0 12px 28px rgba(34,211,238,0.18), 0 0 30px rgba(168,85,247,0.16); }
    .service-icon { background: linear-gradient(135deg, #06b6d4, #8b5cf6); }
    .service-name { color: #e6f2ff; }
    .service-description { color: #a7b4c7; }
    /* Headbar: sticky, pill, neon */
    .headbar { position: sticky; top: 20px; z-index: 20; background: rgba(14,22,46,0.65); border: 1px solid rgba(120,200,255,0.28); border-radius: 14px; backdrop-filter: blur(10px); padding: 10px; margin-bottom: 16px; box-shadow: 0 0 22px rgba(34,211,238,0.15), 0 0 48px rgba(168,85,247,0.12); overflow-x: auto; }
    .headbar::-webkit-scrollbar { height: 0; }
    .headbar-menu { display: flex; gap: 10px; min-width: max-content; }
    .headbar-link { display: inline-flex; align-items: center; padding: 10px 14px; border-radius: 999px; color: #e6f2ff; text-decoration: none; border: 1px solid rgba(120,200,255,0.22); background: linear-gradient(135deg, rgba(10,20,50,0.55), rgba(18,28,56,0.55)); white-space: nowrap; box-shadow: 0 4px 12px rgba(0,0,0,0.25); transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease; }
    .headbar-link:hover { border-color: rgba(140,220,255,0.55); box-shadow: 0 0 12px rgba(34,211,238,0.25), 0 0 20px rgba(168,85,247,0.18); transform: translateY(-1px); }
    .headbar-link.active { background: linear-gradient(135deg, rgba(34,211,238,0.25), rgba(168,85,247,0.25)); border-color: rgba(140,220,255,0.65); }
    /* Sidebar Headbar: muncul dari kanan dengan animasi roll-in */
    .headbar-sidebar { position: fixed; right: 20px; top: 100px; width: 280px; max-height: calc(100vh - 140px); overflow-y: auto; background: rgba(14,22,46,0.75); border: 1px solid rgba(120,200,255,0.28); border-radius: 14px; backdrop-filter: blur(12px); box-shadow: 0 0 22px rgba(34,211,238,0.15), 0 0 48px rgba(168,85,247,0.12); transform: translateX(120%); transition: transform .28s ease; z-index: 50; }
    .headbar-sidebar.open { transform: translateX(0); }
    .headbar-sidebar .title { padding: 12px 14px; font-weight: 600; color: #e6f2ff; border-bottom: 1px solid rgba(120,200,255,0.18); }
    .headbar-sidebar .items { display: flex; flex-direction: column; gap: 8px; padding: 12px; }
    .headbar-sidebar .link { display: flex; align-items: center; padding: 10px 12px; border-radius: 10px; color: #e6f2ff; text-decoration: none; border: 1px solid rgba(120,200,255,0.22); background: linear-gradient(135deg, rgba(10,20,50,0.55), rgba(18,28,56,0.55)); transition: transform .15s ease, border-color .15s ease; }
    .headbar-sidebar .link:hover { border-color: rgba(140,220,255,0.55); transform: translateY(-1px); }
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <aside class="sidebar" id="sidebar">
        <div class="brand"><div class="logo"></div><div class="label"><div class="title">{{ $user->full_name ?? $user->username }}</div><div class="subtitle">{{ $user->getRoleNames()->first() ?? 'Pengguna' }}</div></div><button class="toggle-btn" id="toggleSidebar" aria-label="Toggle Sidebar">◀</button></div>
        <nav class="menu">
            <a href="{{ route('dashboard') }}" id="dashboardMenuLink" class="active" data-icon="dashboard"><span>Dashboard</span><span class="inline-hamburger" aria-hidden="true">☰</span></a>
            <a href="{{ route('dataentry') }}" data-icon="spb"><span>Data Entry</span></a>
            <a href="{{ route('pemantauan') }}" data-icon="shti"><span>Pemantauan</span></a>
            <a href="{{ route('services.sahbandar') }}" data-icon="sahbandar"><span>Sahbandar</span></a>
            <a href="{{ route('services.epit') }}" data-icon="epit"><span>EPIT</span></a>
            <a href="#" data-icon="vtc"><span>VTC</span></a>
            <a href="#" data-icon="cctv"><span>CCTV</span></a>
            <a href="#" data-icon="profil"><span>Profil Pelabuhan</span></a>
            <a href="#" data-icon="kinerja"><span>Kinerja Petugas</span></a>
            <a href="#" data-icon="kapal"><span>Kedatangan & Keberangkatan Kapal</span></a>
            <a href="#" data-icon="pendaratan"><span>Pendaratan Ikan</span></a>
            <a href="#" data-icon="fasilitas"><span>Penggunaan Fasilitas Perikanan</span></a>
            <a href="#" data-icon="pnbp_pasca"><span>PNBP Pasca Produksi</span></a>
            <a href="#" data-icon="pnbp_non_sda"><span>PNBP Non SDA</span></a>
            <a href="#" data-icon="evkin"><span>EVKIN</span></a>
        </nav>
    </aside>
    <main class="page">
    <!-- Sidebar Headbar (muncul saat klik Dashboard) -->
    <aside class="headbar-sidebar" id="headbarSidebar" aria-label="Menu Headbar">
        <div class="title">Menu Layanan</div>
        <div class="items">
            <a href="#" class="link">VTC</a>
            <a href="#" class="link">CCTV</a>
            <a href="#" class="link">Profil Pelabuhan</a>
            <a href="#" class="link">Kinerja Petugas</a>
            <a href="#" class="link">Kedatangan & Keberangkatan Kapal</a>
            <a href="#" class="link">Pendaratan Ikan</a>
            <a href="#" class="link">SHTI</a>
            <a href="#" class="link">Penggunaan Fasilitas Perikanan</a>
            <a href="#" class="link">PNBP Pasca Produksi</a>
            <a href="#" class="link">PNBP Non SDA</a>
            <a href="#" class="link">EVKIN</a>
        </div>
    </aside>
    <!-- Topbar Brand -->
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
                <button class="action-avatar avatar-btn" id="avatarMenuBtn" aria-label="Profil">{{ strtoupper(substr($user->full_name ?? $user->username, 0, 1)) }}</button>
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
    <!-- Header & Statistik dihapus sesuai permintaan -->

    <!-- Services Section -->
    <div class="services-section">
        <h2 class="section-title">
            🌐 Layanan Tersedia
        </h2>
        <div class="services-grid">
            @foreach($services as $service)
            <a href="{{ $service['url'] }}" class="service-card">
                <div class="service-header">
                    <div class="service-icon glow-animated">
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
            <!-- Tambahan kartu layanan futuristik -->
            <a href="#" class="service-card">
                <div class="service-header"><div class="service-icon glow-animated">🎥</div><h3 class="service-name">VTC</h3></div>
                <p class="service-description">Video teleconference internal pelabuhan yang aman.</p>
            </a>
            <a href="#" class="service-card">
                <div class="service-header"><div class="service-icon glow-animated">📹</div><h3 class="service-name">CCTV</h3></div>
                <p class="service-description">Monitoring kamera real-time untuk area operasional.</p>
            </a>
            <a href="#" class="service-card">
                <div class="service-header"><div class="service-icon glow-animated">🏛️</div><h3 class="service-name">Profil Pelabuhan</h3></div>
                <p class="service-description">Informasi fasilitas dan layanan pelabuhan.</p>
            </a>
            <a href="#" class="service-card">
                <div class="service-header"><div class="service-icon glow-animated">👷</div><h3 class="service-name">Kinerja Petugas</h3></div>
                <p class="service-description">Evaluasi performa dan produktivitas petugas.</p>
            </a>
            <a href="#" class="service-card">
                <div class="service-header"><div class="service-icon glow-animated">⚓️</div><h3 class="service-name">Kedatangan & Keberangkatan Kapal</h3></div>
                <p class="service-description">Jadwal dan status pergerakan kapal.</p>
            </a>
            <a href="#" class="service-card">
                <div class="service-header"><div class="service-icon glow-animated">🐟</div><h3 class="service-name">Pendaratan Ikan</h3></div>
                <p class="service-description">Data pendaratan dan distribusi hasil tangkap.</p>
            </a>
            <a href="#" class="service-card">
                <div class="service-header"><div class="service-icon glow-animated">📊</div><h3 class="service-name">SHTI</h3></div>
                <p class="service-description">Sistem informasi terintegrasi untuk pengawasan.</p>
            </a>
            <a href="#" class="service-card">
                <div class="service-header"><div class="service-icon glow-animated">🧰</div><h3 class="service-name">Penggunaan Fasilitas Perikanan</h3></div>
                <p class="service-description">Pemantauan pemanfaatan fasilitas perikanan.</p>
            </a>
            <a href="#" class="service-card">
                <div class="service-header"><div class="service-icon glow-animated">💰</div><h3 class="service-name">PNBP Pasca Produksi</h3></div>
                <p class="service-description">Pendapatan negara dari pasca produksi perikanan.</p>
            </a>
            <a href="#" class="service-card">
                <div class="service-header"><div class="service-icon glow-animated">💳</div><h3 class="service-name">PNBP Non SDA</h3></div>
                <p class="service-description">Pendapatan negara bukan dari sumber daya alam.</p>
            </a>
            <a href="#" class="service-card">
                <div class="service-header"><div class="service-icon glow-animated">✅</div><h3 class="service-name">EVKIN</h3></div>
                <p class="service-description">Evaluasi kinerja berbasis indikator utama.</p>
            </a>
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
    .sidebar.collapsed .brand { justify-content: flex-start; gap: 6px; }
    .sidebar.collapsed .brand .label { display: none; }
    /* Tampilan ikon saat collapsed: sembunyikan teks, tampilkan ikon */
    .sidebar.collapsed .menu a { justify-content: center; padding: 10px; }
    .sidebar.collapsed .menu a::before { display: inline-block; width: 20px; height: 20px; background-repeat: no-repeat; background-size: contain; opacity: 0.9; filter: drop-shadow(0 0 6px rgba(34,211,238,0.7)) drop-shadow(0 0 12px rgba(168,85,247,0.6)); }
    .sidebar.collapsed .menu a::after { content: ''; position: absolute; left: 50%; top: 50%; width: 24px; height: 24px; transform: translate(-50%, -50%); border-radius: 50%; background: radial-gradient(50% 50% at 50% 50%, rgba(34,211,238,0.45) 0%, rgba(168,85,247,0.35) 60%, transparent 100%); filter: blur(2px); opacity: 0.5; display: block; z-index: 0; }
    .sidebar.collapsed .menu a span { display: none; }
    .sidebar.collapsed .toggle-btn { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 16px; margin-left: 0; margin-right: 0; }
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

    // Toggle menu profil pada ikon avatar
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
            if (profileMenu.classList.contains('open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });
        document.addEventListener('click', (e) => {
            if (profileMenu.classList.contains('open') && !profileMenu.contains(e.target)) {
                closeMenu();
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeMenu();
        });
    }

    // Auto-refresh stats every 5 minutes (placeholder)
    setInterval(function() { console.log('Stats refresh interval'); }, 300000);

    // Toggle headbar sidebar saat klik Dashboard
    const dashboardMenuLink = document.getElementById('dashboardMenuLink');
    const headbarSidebar = document.getElementById('headbarSidebar');
    // Hamburger inline di link Dashboard ikut klik anchor
    if (dashboardMenuLink && headbarSidebar) {
        dashboardMenuLink.addEventListener('click', function(e) {
            // Jika sudah berada di dashboard, cegah navigasi dan tampilkan sidebar
            e.preventDefault();
            headbarSidebar.classList.toggle('open');
        });
        // Klik di luar sidebar untuk menutup
        document.addEventListener('click', function(evt) {
            if (headbarSidebar.classList.contains('open')) {
                const inside = headbarSidebar.contains(evt.target) || (dashboardMenuLink.contains && dashboardMenuLink.contains(evt.target));
                if (!inside) headbarSidebar.classList.remove('open');
            }
        });
    }
});
</script>
@endpush
    /* Hamburger inline di link Dashboard */
    .sidebar .menu a[data-icon="dashboard"] { display: flex; align-items: center; justify-content: space-between; }
    .inline-hamburger { font-size: 14px; border: 1px solid rgba(120,200,255,0.28); border-radius: 8px; padding: 3px 6px; color: #a7b4c7; background: rgba(10,20,50,0.35); }
    .sidebar .menu a[data-icon="dashboard"]:hover .inline-hamburger { color: #e6f2ff; border-color: rgba(140,220,255,0.45); }