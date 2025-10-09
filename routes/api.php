<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SSOController;
use App\Http\Controllers\API\Sahbandar\SahbandarController;
use App\Http\Controllers\API\SPB\SPBController;
use App\Http\Controllers\API\SHTI\SHTIController;
use App\Http\Controllers\API\EPIT\EPITController;
use App\Http\Controllers\Monitoring\HealthController;
use App\Http\Controllers\Monitoring\MetricsController;
use App\Http\Controllers\Monitoring\LogsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health Check Routes
Route::get('/health', [HealthController::class, 'basic']);
Route::get('/health/detailed', [HealthController::class, 'detailed'])->middleware(['jwt.auth', 'role:admin']);

// Monitoring and Metrics Routes (Admin only)
Route::prefix('monitoring')->middleware(['jwt.auth', 'role:admin'])->group(function () {
    // System Health
    Route::get('/health', [HealthController::class, 'systemHealth']);
    Route::get('/health/components', [HealthController::class, 'componentHealth']);
    
    // Metrics
    Route::get('/metrics', [MetricsController::class, 'general']);
    Route::get('/metrics/api', [MetricsController::class, 'apiMetrics']);
    Route::get('/metrics/database', [MetricsController::class, 'databaseMetrics']);
    Route::get('/metrics/cache', [MetricsController::class, 'cacheMetrics']);
    Route::get('/metrics/auth', [MetricsController::class, 'authMetrics']);
    Route::get('/metrics/sso', [MetricsController::class, 'ssoMetrics']);
    Route::get('/metrics/security', [MetricsController::class, 'securityMetrics']);
    Route::get('/metrics/performance', [MetricsController::class, 'performanceMetrics']);
    Route::get('/metrics/export', [MetricsController::class, 'exportMetrics']);
    
    // Logs
    Route::get('/logs', [LogsController::class, 'search']);
    Route::get('/logs/stats', [LogsController::class, 'statistics']);
    Route::get('/logs/export', [LogsController::class, 'export']);
    Route::get('/logs/security', [LogsController::class, 'securityLogs']);
    Route::get('/logs/audit', [LogsController::class, 'auditLogs']);
    Route::get('/logs/performance', [LogsController::class, 'performanceLogs']);
});

// Public Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('rate.limit:5,1');
    Route::post('/register', [AuthController::class, 'register'])->middleware('rate.limit:3,5');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('rate.limit:10,1');
});

// SSO Routes (for service-to-service communication)
Route::prefix('sso')->group(function () {
    // Public SSO endpoints
    Route::post('/login', [SSOController::class, 'login'])->middleware('rate.limit:10,1');
    Route::get('/callback', [SSOController::class, 'callback']);
    
    // Protected SSO endpoints
    Route::middleware(['jwt.auth'])->group(function () {
        Route::post('/logout', [SSOController::class, 'logout']);
        Route::get('/sessions', [SSOController::class, 'getSessions']);
        Route::delete('/sessions/{sessionId}', [SSOController::class, 'revokeSession']);
    });
    
    // Service token validation (for microservices)
    Route::post('/validate', [SSOController::class, 'validateToken'])->middleware('rate.limit:100,1');
});

// Protected User Routes
Route::middleware(['jwt.auth'])->group(function () {
    // User profile and authentication
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('rate.limit:3,5');
});

// Sahbandar Service API Routes
Route::prefix('sahbandar')->middleware(['jwt.auth', 'service.access:sahbandar', 'rate.limit:60,1'])->group(function () {
    Route::get('/profile', [SahbandarController::class, 'getUserProfile']);
    Route::get('/vessels', [SahbandarController::class, 'getVessels']);
    Route::get('/ports', [SahbandarController::class, 'getPorts']);
    Route::get('/port-activities', [SahbandarController::class, 'getPortActivities']);
    Route::get('/clearances', [SahbandarController::class, 'getClearances']);
    Route::post('/clearances', [SahbandarController::class, 'createClearance']);
    Route::put('/clearances/{id}', [SahbandarController::class, 'updateClearance']);
    Route::get('/clearances/history', [SahbandarController::class, 'getClearanceHistory']);
    Route::get('/dashboard', [SahbandarController::class, 'getDashboard']);
    Route::post('/sync', [SahbandarController::class, 'syncData']);
});

// SPB Service API Routes
Route::prefix('spb')->middleware(['jwt.auth', 'service.access:spb', 'rate.limit:60,1'])->group(function () {
    Route::get('/profile', [SPBController::class, 'getUserProfile']);
    Route::get('/applications', [SPBController::class, 'getApplications']);
    Route::get('/applications/{id}', [SPBController::class, 'getApplication']);
    Route::post('/applications', [SPBController::class, 'createApplication']);
    Route::put('/applications/{id}/status', [SPBController::class, 'updateApplicationStatus']);
    Route::post('/certificates/issue', [SPBController::class, 'issueCertificate']);
    Route::get('/certificates', [SPBController::class, 'getCertificates']);
    Route::get('/certificates/{id}/verify', [SPBController::class, 'verifyCertificate']);
    Route::get('/vessels/tracking', [SPBController::class, 'getVesselTracking']);
    Route::get('/dashboard', [SPBController::class, 'getDashboard']);
    Route::get('/reports', [SPBController::class, 'generateReports']);
    Route::post('/sync', [SPBController::class, 'syncData']);
});

// SHTI Service API Routes
Route::prefix('shti')->middleware(['jwt.auth', 'service.access:shti', 'rate.limit:60,1'])->group(function () {
    Route::get('/profile', [SHTIController::class, 'getUserProfile']);
    Route::get('/catch-reports', [SHTIController::class, 'getCatchReports']);
    Route::get('/catch-reports/{id}', [SHTIController::class, 'getCatchReport']);
    Route::post('/catch-reports', [SHTIController::class, 'createCatchReport']);
    Route::put('/catch-reports/{id}/status', [SHTIController::class, 'updateReportStatus']);
    Route::get('/fishing-vessels', [SHTIController::class, 'getFishingVessels']);
    Route::get('/fishing-quotas', [SHTIController::class, 'getFishingQuotas']);
    Route::get('/catch-statistics', [SHTIController::class, 'getCatchStatistics']);
    Route::get('/fishing-licenses/{id}/validate', [SHTIController::class, 'validateFishingLicense']);
    Route::get('/fishing-areas', [SHTIController::class, 'getFishingAreas']);
    Route::get('/dashboard', [SHTIController::class, 'getDashboard']);
    Route::get('/reports', [SHTIController::class, 'generateReports']);
    Route::post('/sync', [SHTIController::class, 'syncData']);
});

// EPIT Service API Routes
Route::prefix('epit')->middleware(['jwt.auth', 'service.access:epit', 'rate.limit:60,1'])->group(function () {
    Route::get('/profile', [EPITController::class, 'getUserProfile']);
    Route::get('/port-systems', [EPITController::class, 'getPortSystems']);
    Route::get('/port-systems/{id}', [EPITController::class, 'getPortSystem']);
    Route::get('/vessel-tracking', [EPITController::class, 'getVesselTracking']);
    Route::get('/port-operations', [EPITController::class, 'getPortOperations']);
    Route::post('/port-operations', [EPITController::class, 'createPortOperation']);
    Route::put('/port-operations/{id}/status', [EPITController::class, 'updateOperationStatus']);
    Route::get('/berth-availability', [EPITController::class, 'getBerthAvailability']);
    Route::get('/cargo-statistics', [EPITController::class, 'getCargoStatistics']);
    Route::get('/port-performance', [EPITController::class, 'getPortPerformance']);
    Route::get('/weather-conditions', [EPITController::class, 'getWeatherConditions']);
    Route::get('/port-facilities', [EPITController::class, 'getPortFacilities']);
    Route::get('/vessel-schedules', [EPITController::class, 'getVesselSchedules']);
    Route::get('/dashboard', [EPITController::class, 'getDashboard']);
    Route::get('/reports', [EPITController::class, 'generateReports']);
    Route::post('/sync', [EPITController::class, 'syncData']);
    Route::get('/port-status/realtime', [EPITController::class, 'getRealtimePortStatus']);
});

// Service-to-Service Communication Routes (using SSO tokens)
Route::prefix('services')->middleware(['sso.token', 'rate.limit:200,1'])->group(function () {
    // Sahbandar service endpoints
    Route::prefix('sahbandar')->middleware('sso.token:sahbandar')->group(function () {
        Route::get('/user/{userId}', [SahbandarController::class, 'getUserProfile']);
        Route::get('/vessels', [SahbandarController::class, 'getVessels']);
        Route::get('/clearances', [SahbandarController::class, 'getClearances']);
    });
    
    // SPB service endpoints
    Route::prefix('spb')->middleware('sso.token:spb')->group(function () {
        Route::get('/user/{userId}', [SPBController::class, 'getUserProfile']);
        Route::get('/applications', [SPBController::class, 'getApplications']);
        Route::get('/certificates', [SPBController::class, 'getCertificates']);
    });
    
    // SHTI service endpoints
    Route::prefix('shti')->middleware('sso.token:shti')->group(function () {
        Route::get('/user/{userId}', [SHTIController::class, 'getUserProfile']);
        Route::get('/catch-reports', [SHTIController::class, 'getCatchReports']);
        Route::get('/fishing-vessels', [SHTIController::class, 'getFishingVessels']);
    });
    
    // EPIT service endpoints
    Route::prefix('epit')->middleware('sso.token:epit')->group(function () {
        Route::get('/user/{userId}', [EPITController::class, 'getUserProfile']);
        Route::get('/port-systems', [EPITController::class, 'getPortSystems']);
        Route::get('/vessel-tracking', [EPITController::class, 'getVesselTracking']);
    });
});