<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SSOController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// SSO Routes
Route::prefix('sso')->name('sso.')->group(function () {
    Route::get('/login', [SSOController::class, 'login'])->name('login');
    Route::get('/callback', [SSOController::class, 'callback'])->name('callback');
    Route::post('/logout', [SSOController::class, 'logout'])->name('logout');
    Route::get('/validate', [SSOController::class, 'validate'])->name('validate');
});

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // Service Access Routes
    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/sahbandar', function () {
            return redirect()->away(config('services.sahbandar.url'));
        })->name('sahbandar');
        
        Route::get('/spb', function () {
            return redirect()->away(config('services.spb.url'));
        })->name('spb');
        
        Route::get('/shti', function () {
            return redirect()->away(config('services.shti.url'));
        })->name('shti');
        
        Route::get('/epit', function () {
            return redirect()->away(config('services.epit.url'));
        })->name('epit');
    });
});

// Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'service' => 'PIPP SSO System'
    ]);
})->name('health');