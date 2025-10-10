@extends('layouts.app')

@push('styles')
<style>
    .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        animation: fadeIn 0.8s ease-in-out;
    }
    
    .login-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 400px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .login-header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .login-logo {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        display: block;
    }
    
    .login-title {
        color: #1e40af;
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .login-subtitle {
        color: #64748b;
        font-size: 14px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        color: #374151;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 8px;
    }
    
    .form-input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: white;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .login-button {
        width: 100%;
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        color: white;
        border: none;
        padding: 14px;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 10px;
    }
    
    .login-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
    }
    
    .login-button:active {
        transform: translateY(0);
    }
    
    .forgot-password {
        text-align: center;
        margin-top: 20px;
    }
    
    .forgot-password a {
        color: #3b82f6;
        text-decoration: none;
        font-size: 14px;
    }
    
    .forgot-password a:hover {
        text-decoration: underline;
    }
    
    .error-message {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
    }
    
    @keyframes fadeIn {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @media (max-width: 768px) {
        .login-card {
            padding: 30px 20px;
            margin: 10px;
        }
        
        .login-title {
            font-size: 20px;
        }
    }
</style>
@endpush

@section('content')
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <img src="{{ asset('images/kkp-logo.svg') }}" alt="Logo KKP" class="login-logo">
            <h1 class="login-title">Masuk ke Sistem</h1>
            <p class="login-subtitle">Single Sign-On PIPP</p>
        </div>
        
        @if ($errors->any())
            <div class="error-message">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <form method="POST" action="{{ route('login') }}">
            @csrf
            
            <div class="form-group">
                <label for="username" class="form-label">Username atau Email</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-input" 
                    value="{{ old('username') }}" 
                    required 
                    autofocus
                    placeholder="Masukkan username atau email"
                >
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input" 
                    required
                    placeholder="Masukkan password"
                >
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; font-size: 14px; color: #374151;">
                    <input type="checkbox" name="remember" style="margin-right: 8px;">
                    Ingat saya
                </label>
            </div>
            
            <button type="submit" class="login-button">
                Masuk
            </button>
        </form>
        
        <div class="forgot-password">
            <a href="#" onclick="alert('Fitur reset password akan segera tersedia')">
                Lupa password?
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add some interactive effects to form inputs
    const inputs = document.querySelectorAll('.form-input');
    
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'translateY(-2px)';
            this.parentElement.style.transition = 'transform 0.3s ease';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'translateY(0)';
        });
    });
    
    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        
        if (!username || !password) {
            e.preventDefault();
            alert('Mohon lengkapi semua field yang diperlukan');
        }
    });
});
</script>
@endpush