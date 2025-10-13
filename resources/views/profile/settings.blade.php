@extends('layouts.app')

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
        --neon-primary: #22d3ee;
        --neon-secondary: #a855f7;
        --shadow-neon: 0 0 20px rgba(34, 211, 238, 0.15), 0 0 40px rgba(168, 85, 247, 0.12);
    }
    .profile-container {
        min-height: 100vh;
        background: var(--neon-bg);
        color: var(--text-primary);
        padding: 20px;
    }
    .topbar { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 12px; backdrop-filter: blur(10px); padding: 10px 12px; margin: 0 auto 16px; max-width: 800px; box-shadow: var(--shadow-neon); display: flex; align-items: center; justify-content: space-between; }
    .header-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 0; }
    .brand-logo { height: clamp(36px, 5vw, 56px); width: auto; background: transparent; box-shadow: none; border-radius: 0; display: block; }
    .brand-text .brand-title { font-size: 20px; font-weight: 700; background: linear-gradient(90deg, var(--neon-primary), var(--neon-secondary)); -webkit-background-clip: text; background-clip: text; color: transparent; letter-spacing: 0.4px; }
    .brand-text .brand-subtitle { color: var(--text-muted); font-size: 12px; }
    .back-btn { background: transparent; border: 1px solid var(--glass-border); color: var(--text-muted); border-radius: 10px; padding: 8px 10px; text-decoration: none; transition: all .2s ease; }
    .back-btn:hover { color: var(--text-primary); border-color: rgba(140,220,255,0.45); transform: translateY(-1px); }
    .profile-card {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        max-width: 800px;
        box-shadow: var(--shadow-neon);
    }
    .section-title {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-row { display: grid; gap: 24px; margin-bottom: 20px; grid-template-columns: 1fr 1fr; align-items: start; }
    .form-row.row-ue { grid-template-columns: 1fr 1fr; }
    .form-row.row-name { grid-template-columns: 1fr 1fr; }
    .form-row.row-phone { grid-template-columns: 1fr; }
    .form-row.row-role { grid-template-columns: 1fr; }
    .form-row.row-password { grid-template-columns: 1fr 1fr; }
    .form-row.row-password-confirm { grid-template-columns: 1fr; }
    .form-group { min-width: 0; }
    @media (max-width: 768px) {
        .form-row { grid-template-columns: 1fr; }
    }
    .form-group label {
        color: var(--text-primary);
        font-weight: 600;
        margin-bottom: 8px;
        display: block;
    }
    .form-group input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--glass-border);
        border-radius: 10px;
        background: rgba(12, 20, 40, 0.55);
        color: var(--text-primary);
        transition: border-color 0.2s ease;
    }
    .form-group input::placeholder { color: var(--text-muted); }
    .form-group input:focus {
        outline: none;
        border-color: rgba(140, 220, 255, 0.45);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }
    .avatar-section {
        display: flex;
        align-items: center;
        gap: 16px;
        margin: 10px 0 20px;
    }
    .avatar-preview {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--neon-primary), var(--neon-secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 20px;
        overflow: hidden;
        box-shadow: 0 6px 18px rgba(34, 211, 238, 0.25);
    }
    .update-btn {
        background: #3b82f6;
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .update-btn:hover {
        background: #2563eb;
        transform: translateY(-2px);
    }
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #10b981;
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 16px;
        font-weight: 600;
    }
    /* Popup notice */
    .popup-notice { position: fixed; inset: 0; display: none; align-items: center; justify-content: center; }
    .popup-notice.show { display: flex; }
    .popup-card { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 12px; box-shadow: var(--shadow-neon); padding: 20px; max-width: 420px; text-align: center; }
    .popup-card .title { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
    .popup-card .desc { color: var(--text-muted); margin-bottom: 16px; }
    .popup-card button { background: #3b82f6; color: #fff; border: none; border-radius: 10px; padding: 10px 16px; font-weight: 600; cursor: pointer; }
    .role-current { margin: -4px 0 16px; color: var(--text-muted); }
    .role-badge { display: inline-block; padding: 4px 10px; border: 1px solid var(--glass-border); border-radius: 999px; background: rgba(12, 20, 40, 0.55); color: var(--text-primary); font-weight: 600; }
</style>
@endpush

@section('content')
<div class="profile-container">
    <div class="topbar">
        <div class="header-brand">
            <img src="/images/logo-kkp-pipp.png" class="brand-logo" alt="KKP PIPP Logo">
            <div class="brand-text">
                <div class="brand-title">SSO PIPP</div>
                <div class="brand-subtitle">Single Sign-On KKP</div>
            </div>
        </div>
        <a class="back-btn" href="{{ route('dashboard') }}">← Kembali ke Dashboard</a>
    </div>
    <div class="profile-card">
        <h2 class="section-title">Pengaturan Profil</h2>
        @php($currentRole = $user->role ?? 'User')
        <div class="role-current">Role saat ini: <span class="role-badge">{{ $currentRole }}</span></div>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-row row-ue">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}" required placeholder="username">
                    @error('username')<small style="color:#ef4444">{{ $message }}</small>@enderror
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required placeholder="email@domain.com">
                    @error('email')<small style="color:#ef4444">{{ $message }}</small>@enderror
                </div>
            </div>

            <div class="form-row row-name">
                <div class="form-group">
                    <label for="first_name">Nama Depan</label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required placeholder="Nama depan">
                    @error('first_name')<small style="color:#ef4444">{{ $message }}</small>@enderror
                </div>
                <div class="form-group">
                    <label for="last_name">Nama Belakang</label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required placeholder="Nama belakang">
                    @error('last_name')<small style="color:#ef4444">{{ $message }}</small>@enderror
                </div>
            </div>

            <div class="form-row row-phone">
                <div class="form-group">
                    <label for="phone">No. Telepon</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="08xxxxxxxxxx">
                    @error('phone')<small style="color:#ef4444">{{ $message }}</small>@enderror
                </div>
            </div>

            <div class="form-row row-role">
                <div class="form-group">
                    <label for="role">Role</label>
                    @php($currentRole = $user->role ?? 'User')
                    <select id="role" name="role" style="width:100%; padding:10px 12px; border:1px solid var(--glass-border); border-radius:10px; background: rgba(12, 20, 40, 0.55); color: var(--text-primary);">
                        <option value="User" {{ old('role', $currentRole) === 'User' ? 'selected' : '' }}>User</option>
                        <option value="Administrator" {{ old('role', $currentRole) === 'Administrator' ? 'selected' : '' }}>Administrator</option>
                        <option value="Viewer" {{ old('role', $currentRole) === 'Viewer' ? 'selected' : '' }}>Viewer</option>
                    </select>
                    @error('role')<small style="color:#ef4444">{{ $message }}</small>@enderror
                </div>
            </div>

            <div class="section-title" style="margin-top: 8px;">Keamanan</div>
            <div class="form-row row-password">
                <div class="form-group">
                    <label for="current_password">Password Saat Ini</label>
                    <input type="password" id="current_password" name="current_password" placeholder="••••••••">
                    @error('current_password')<small style="color:#ef4444">{{ $message }}</small>@enderror
                </div>
                <div class="form-group">
                    <label for="new_password">Ganti Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Password baru">
                    @error('new_password')<small style="color:#ef4444">{{ $message }}</small>@enderror
                </div>
            </div>
            <div class="form-row row-password-confirm">
                <div class="form-group">
                    <label for="new_password_confirmation">Konfirmasi Password Baru</label>
                    <input type="password" id="new_password_confirmation" name="new_password_confirmation" placeholder="Ulangi password baru">
                </div>
            </div>

            <div class="avatar-section">
                <div class="avatar-preview">
                    @php($avatar = $user->preferences['avatar_path'] ?? null)
                    @if($avatar)
                        <img src="{{ asset('storage/'.$avatar) }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                    @else
                        {{ strtoupper(substr($user->first_name,0,1)) }}
                    @endif
                </div>
                <div class="form-group" style="flex:1;">
                    <label for="photo">Foto</label>
                    <input type="file" id="photo" name="photo" accept="image/*">
                    @error('photo')<small style="color:#ef4444">{{ $message }}</small>@enderror
                </div>
            </div>

            <button type="submit" class="update-btn">Update</button>
        </form>
        <div id="emailVerifyPopup" class="popup-notice" aria-hidden="true">
            <div class="popup-card" role="dialog" aria-modal="true">
                <div class="title">Verifikasi Perubahan Password</div>
                <div class="desc">Silakan cek email Anda untuk verifikasi perubahan password.</div>
                <button type="button" onclick="document.getElementById('emailVerifyPopup').classList.remove('show')">OK</button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const form = document.querySelector('.profile-card form');
  const popup = document.getElementById('emailVerifyPopup');
  const newPw = document.getElementById('new_password');
  if (form) {
    form.addEventListener('submit', function(){
      if (newPw && newPw.value.trim().length > 0) {
        popup.classList.add('show');
        setTimeout(()=>{ try{ popup.classList.remove('show'); }catch(e){} }, 3500);
      }
    });
  }
});
</script>
@endsection