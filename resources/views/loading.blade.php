@extends('layouts.app')

@push('styles')
<style>
    .loading-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #10b981 100%);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        animation: fadeOut 0.5s ease-in-out 2s forwards;
    }
    
    .logo-container {
        position: relative;
        margin-bottom: 30px;
    }
    
    .logo {
        width: 150px;
        height: 150px;
        animation: logoEntrance 1s ease-out;
    }
    
    .logo-glow {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
        animation: pulse 2s ease-in-out infinite;
    }
    
    .loading-text {
        color: white;
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 20px;
        text-align: center;
        animation: textSlideUp 1s ease-out 0.5s both;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    
    .loading-subtitle {
        color: rgba(255,255,255,0.9);
        font-size: 16px;
        font-weight: 400;
        text-align: center;
        animation: textSlideUp 1s ease-out 0.7s both;
        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    }
    
    .loading-spinner {
        margin-top: 30px;
        position: relative;
    }
    
    .spinner {
        width: 60px;
        height: 60px;
        border: 4px solid rgba(255,255,255,0.3);
        border-top: 4px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    .progress-bar {
        width: 200px;
        height: 4px;
        background: rgba(255,255,255,0.3);
        border-radius: 2px;
        margin-top: 20px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #fbbf24, #f59e0b);
        border-radius: 2px;
        animation: progressFill 2s ease-in-out;
        box-shadow: 0 0 10px rgba(251, 191, 36, 0.5);
    }
    
    .floating-particles {
        position: absolute;
        width: 100%;
        height: 100%;
        overflow: hidden;
        pointer-events: none;
    }
    
    .particle {
        position: absolute;
        width: 4px;
        height: 4px;
        background: rgba(255,255,255,0.6);
        border-radius: 50%;
        animation: float 3s ease-in-out infinite;
    }
    
    .particle:nth-child(1) { left: 10%; animation-delay: 0s; }
    .particle:nth-child(2) { left: 20%; animation-delay: 0.5s; }
    .particle:nth-child(3) { left: 30%; animation-delay: 1s; }
    .particle:nth-child(4) { left: 40%; animation-delay: 1.5s; }
    .particle:nth-child(5) { left: 50%; animation-delay: 2s; }
    .particle:nth-child(6) { left: 60%; animation-delay: 0.3s; }
    .particle:nth-child(7) { left: 70%; animation-delay: 0.8s; }
    .particle:nth-child(8) { left: 80%; animation-delay: 1.3s; }
    .particle:nth-child(9) { left: 90%; animation-delay: 1.8s; }
    
    @keyframes logoEntrance {
        0% {
            transform: scale(0) rotate(-180deg);
            opacity: 0;
        }
        50% {
            transform: scale(1.1) rotate(-90deg);
        }
        100% {
            transform: scale(1) rotate(0deg);
            opacity: 1;
        }
    }
    
    @keyframes pulse {
        0%, 100% {
            transform: translate(-50%, -50%) scale(1);
            opacity: 0.7;
        }
        50% {
            transform: translate(-50%, -50%) scale(1.1);
            opacity: 0.3;
        }
    }
    
    @keyframes textSlideUp {
        0% {
            transform: translateY(30px);
            opacity: 0;
        }
        100% {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @keyframes progressFill {
        0% { width: 0%; }
        100% { width: 100%; }
    }
    
    @keyframes float {
        0%, 100% {
            transform: translateY(100vh) scale(0);
            opacity: 0;
        }
        10% {
            opacity: 1;
            transform: translateY(90vh) scale(1);
        }
        90% {
            opacity: 1;
            transform: translateY(-10vh) scale(1);
        }
        100% {
            transform: translateY(-20vh) scale(0);
            opacity: 0;
        }
    }
    
    @keyframes fadeOut {
        0% {
            opacity: 1;
            visibility: visible;
        }
        100% {
            opacity: 0;
            visibility: hidden;
        }
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
        .logo {
            width: 120px;
            height: 120px;
        }
        
        .logo-glow {
            width: 150px;
            height: 150px;
        }
        
        .loading-text {
            font-size: 20px;
        }
        
        .loading-subtitle {
            font-size: 14px;
        }
        
        .progress-bar {
            width: 150px;
        }
    }
</style>
@endpush

@section('content')
<div class="loading-container" id="loadingScreen">
    <!-- Floating particles background -->
    <div class="floating-particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    
    <!-- Logo with glow effect -->
    <div class="logo-container">
        <div class="logo-glow"></div>
        <img src="{{ asset('images/kkp-logo.svg') }}" alt="Logo KKP" class="logo">
    </div>
    
    <!-- Loading text -->
    <h1 class="loading-text">Sistem Single Sign-On</h1>
    
    
    <!-- Loading spinner and progress -->
    <div class="loading-spinner">
        <div class="spinner"></div>
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto redirect to login after 2.5 seconds
    setTimeout(function() {
        window.location.href = '{{ route("login") }}';
    }, 2500);
    
    // Add some interactive effects
    const logo = document.querySelector('.logo');
    const particles = document.querySelectorAll('.particle');
    
    // Add random sizes to particles
    particles.forEach(particle => {
        const size = Math.random() * 6 + 2;
        particle.style.width = size + 'px';
        particle.style.height = size + 'px';
        particle.style.animationDuration = (Math.random() * 2 + 2) + 's';
    });
    
    // Logo hover effect (if user hovers during loading)
    logo.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1) rotate(5deg)';
        this.style.transition = 'transform 0.3s ease';
    });
    
    logo.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1) rotate(0deg)';
    });
});
</script>
@endpush