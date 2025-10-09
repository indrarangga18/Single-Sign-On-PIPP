<?php

namespace App\Http\Controllers\API\SHTI;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\Microservices\SHTIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SHTIController extends Controller
{
    protected $shtiService;

    public function __construct(SHTIService $shtiService)
    {
        $this->shtiService = $shtiService;
        $this->middleware('auth:api');
        $this->middleware('permission:access shti|manage shti');
    }

    /**
     * Get user profile for SHTI service.
     */
    public function getUserProfile(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'shti', 'user_profile');

            $profile = [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'nip' => $user->nip,
                'position' => $user->position,
                'department' => $user->department,
                'office_location' => $user->office_location,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->getAllPermissions()
                    ->filter(function ($permission) {
                        return str_contains($permission->name, 'shti');
                    })
                    ->pluck('name'),
            ];

            return response()->json([
                'success' => true,
                'data' => $profile
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fishing catch reports.
     */
    public function getCatchReports(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['page', 'limit', 'search', 'vessel_id', 'fishing_area', 'date_from', 'date_to', 'status']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'shti', 'catch_reports_list');

            $reports = $this->shtiService->getCatchReports($params);

            return response()->json([
                'success' => true,
                'data' => $reports
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get catch reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific catch report details.
     */
    public function getCatchReport(Request $request, $reportId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'shti', "catch_report_detail:{$reportId}");

            $report = $this->shtiService->getCatchReport($reportId);

            if (!$report) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catch report not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get catch report details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new catch report.
     */
    public function createCatchReport(Request $request): JsonResponse
    {
        $request->validate([
            'vessel_id' => 'required|string',
            'vessel_name' => 'required|string|max:255',
            'captain_name' => 'required|string|max:255',
            'fishing_license' => 'required|string',
            'departure_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:departure_date',
            'fishing_area' => 'required|string',
            'fishing_coordinates' => 'required|array',
            'fishing_coordinates.latitude' => 'required|numeric|between:-90,90',
            'fishing_coordinates.longitude' => 'required|numeric|between:-180,180',
            'catch_data' => 'required|array',
            'catch_data.*.species' => 'required|string',
            'catch_data.*.quantity' => 'required|numeric|min:0',
            'catch_data.*.weight' => 'required|numeric|min:0',
            'catch_data.*.unit' => 'required|string|in:kg,ton,pieces',
            'fishing_gear' => 'required|string',
            'crew_count' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        try {
            $user = Auth::user();
            
            // Check permission for creating report
            if (!$user->hasPermissionTo('manage shti')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to create catch report'
                ], 403);
            }

            $reportData = $request->validated();
            $reportData['reporter_id'] = $user->id;
            $reportData['status'] = 'pending';
            
            // Log service access
            AuditLog::logServiceAccess($user, 'shti', 'create_catch_report');

            $report = $this->shtiService->createCatchReport($reportData);

            return response()->json([
                'success' => true,
                'message' => 'Catch report created successfully',
                'data' => $report
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create catch report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update catch report status.
     */
    public function updateReportStatus(Request $request, $reportId): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:pending,verified,approved,rejected',
            'verification_notes' => 'nullable|string',
            'inspector_id' => 'nullable|string',
        ]);

        try {
            $user = Auth::user();
            
            // Check permission for updating report
            if (!$user->hasPermissionTo('manage shti')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to update catch report'
                ], 403);
            }

            $updateData = $request->validated();
            $updateData['verified_by'] = $user->id;
            $updateData['verified_at'] = now();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'shti', "update_report:{$reportId}");

            $report = $this->shtiService->updateReportStatus($reportId, $updateData);

            return response()->json([
                'success' => true,
                'message' => 'Catch report status updated successfully',
                'data' => $report
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update catch report status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fishing vessels.
     */
    public function getFishingVessels(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['page', 'limit', 'search', 'vessel_type', 'license_status', 'port_base']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'shti', 'fishing_vessels_list');

            $vessels = $this->shtiService->getFishingVessels($params);

            return response()->json([
                'success' => true,
                'data' => $vessels
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get fishing vessels',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fishing quotas.
     */
    public function getFishingQuotas(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['fishing_area', 'species', 'year', 'quarter']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'shti', 'fishing_quotas');

            $quotas = $this->shtiService->getFishingQuotas($params);

            return response()->json([
                'success' => true,
                'data' => $quotas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get fishing quotas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get catch statistics.
     */
    public function getCatchStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['fishing_area', 'species', 'date_from', 'date_to', 'group_by']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'shti', 'catch_statistics');

            $statistics = $this->shtiService->getCatchStatistics($params);

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get catch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate fishing license.
     */
    public function validateFishingLicense(Request $request): JsonResponse
    {
        $request->validate([
            'license_number' => 'required|string',
        ]);

        try {
            $user = Auth::user();
            $licenseNumber = $request->input('license_number');
            
            // Log service access
            AuditLog::logServiceAccess($user, 'shti', "validate_license:{$licenseNumber}");

            $validation = $this->shtiService->validateFishingLicense($licenseNumber);

            return response()->json([
                'success' => true,
                'data' => $validation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate fishing license',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fishing areas.
     */
    public function getFishingAreas(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['region', 'zone_type', 'status']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'shti', 'fishing_areas');

            $areas = $this->shtiService->getFishingAreas($params);

            return response()->json([
                'success' => true,
                'data' => $areas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get fishing areas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics for SHTI.
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'shti', 'dashboard_stats');

            $stats = $this->shtiService->getDashboardStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate SHTI report.
     */
    public function generateReport(Request $request): JsonResponse
    {
        $request->validate([
            'report_type' => 'required|string|in:catch_summary,vessel_activity,quota_utilization,statistics',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'fishing_area' => 'nullable|string',
            'species' => 'nullable|string',
            'format' => 'nullable|string|in:pdf,excel,csv',
        ]);

        try {
            $user = Auth::user();
            
            // Check permission for generating reports
            if (!$user->hasPermissionTo('manage shti')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to generate reports'
                ], 403);
            }

            $reportParams = $request->validated();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'shti', 'generate_report');

            $report = $this->shtiService->generateReport($reportParams);

            return response()->json([
                'success' => true,
                'message' => 'Report generated successfully',
                'data' => $report
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync data with SHTI service.
     */
    public function syncData(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check permission for data sync
            if (!$user->hasPermissionTo('manage shti')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to sync data'
                ], 403);
            }

            // Log service access
            AuditLog::logServiceAccess($user, 'shti', 'data_sync');

            $syncResult = $this->shtiService->syncData();

            return response()->json([
                'success' => true,
                'message' => 'Data synchronization completed',
                'data' => $syncResult
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}