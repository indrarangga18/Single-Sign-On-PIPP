@extends('layouts.app')

@push('styles')
<style>
    .pemantauan-container {
        min-height: 100vh;
        background: radial-gradient(1200px 600px at 10% 10%, rgba(0,255,255,0.08), transparent 60%),
                    radial-gradient(1000px 500px at 90% 20%, rgba(168,85,247,0.12), transparent 55%),
                    linear-gradient(135deg, #0b1020, #0d1b2a 60%, #0b1020);
        padding: 20px;
        color: #e6f2ff;
    }
    .pemantauan-card {
        background: rgba(18,28,56,0.55);
        border: 1px solid rgba(120,200,255,0.25);
        border-radius: 14px;
        backdrop-filter: blur(10px);
        box-shadow: 0 0 20px rgba(34, 211, 238, 0.15), 0 0 40px rgba(168, 85, 247, 0.12);
        padding: 24px;
        max-width: 960px;
        margin: 0 auto;
    }
    .pemantauan-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
    .pemantauan-title { font-size: 24px; font-weight: 700; background: linear-gradient(90deg, #22d3ee, #a855f7); -webkit-background-clip: text; background-clip: text; color: transparent; letter-spacing: 0.4px; }
    .pemantauan-desc { color: #a7b4c7; font-size: 14px; margin-bottom: 20px; }
    .placeholder { border: 1px dashed rgba(120,200,255,0.35); border-radius: 12px; padding: 18px; color: #a7b4c7; text-align: center; }
    .placeholder strong { color: #e6f2ff; }
    .back-link { display: inline-block; margin-top: 16px; color: #e6f2ff; text-decoration: none; border: 1px solid rgba(120,200,255,0.28); border-radius: 10px; padding: 8px 12px; }
    .back-link:hover { border-color: rgba(140,220,255,0.45); }
</style>
@endpush

@section('content')
<div class="pemantauan-container">
    <div class="pemantauan-card">
        <div class="pemantauan-header">
            <div class="pemantauan-title">Pemantauan</div>
        </div>
        <div class="pemantauan-desc">Halaman Pemantauan untuk melihat status dan aktivitas terkini. Konten akan dikembangkan sesuai kebutuhan.</div>
        <div class="placeholder">Ini adalah halaman placeholder <strong>Pemantauan</strong>. Grafik, tabel, dan ringkasan akan ditambahkan.</div>
        <a href="{{ route('dashboard') }}" class="back-link">← Kembali ke Dashboard</a>
    </div>
    </div>
@endsection
