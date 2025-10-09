<?php

namespace App\Http\Controllers\API\SPB;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\Microservices\SPBService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SPBController extends Controller
{
    protected $spbService;

    public function __construct(SPBService $spbService)
    {
        $this->spbService = $spbService;
        $this->middleware('auth:api');
        $this->middleware('permission:access spb|manage spb');
    }

    /**
     * Get user profile for SPB service.
     */
    public function getUserProfile(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'spb', 'user_profile');

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
                        return str_contains($permission->name, 'spb');
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
     * Get SPB applications list.
     */
    public function getApplications(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['page', 'limit', 'search', 'status', 'vessel_type', 'date_from', 'date_to']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'spb', 'applications_list');

            $applications = $this->spbService->getApplications($params);

            return response()->json([
                'success' => true,
                'data' => $applications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get SPB applications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific SPB application details.
     */
    public function getApplication(Request $request, $applicationId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'spb', "application_detail:{$applicationId}");

            $application = $this->spbService->getApplication($applicationId);

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'SPB application not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $application
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get SPB application details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new SPB application.
     */
    public function createApplication(Request $request): JsonResponse
    {
        $request->validate([
            'vessel_name' => 'required|string|max:255',
            'vessel_type' => 'required|string',
            'vessel_flag' => 'required|string',
            'vessel_owner' => 'required|string|max:255',
            'captain_name' => 'required|string|max:255',
            'departure_port' => 'required|string',
            'destination_port' => 'required|string',
            'departure_date' => 'required|date',
            'cargo_type' => 'nullable|string',
            'cargo_weight' => 'nullable|numeric',
            'passenger_count' => 'nullable|integer',
            'crew_count' => 'required|integer',
            'documents' => 'required|array',
            'notes' => 'nullable|string',
        ]);

        try {
            $user = Auth::user();
            
            // Check permission for creating application
            if (!$user->hasPermissionTo('manage spb')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to create SPB application'
                ], 403);
            }

            $applicationData = $request->validated();
            $applicationData['applicant_id'] = $user->id;
            $applicationData['status'] = 'pending';
            
            // Log service access
            AuditLog::logServiceAccess($user, 'spb', 'create_application');

            $application = $this->spbService->createApplication($applicationData);

            return response()->json([
                'success' => true,
                'message' => 'SPB application created successfully',
                'data' => $application
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create SPB application',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update SPB application status.
     */
    public function updateApplicationStatus(Request $request, $applicationId): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:pending,under_review,approved,rejected,issued',
            'reviewer_notes' => 'nullable|string',
            'conditions' => 'nullable|array',
        ]);

        try {
            $user = Auth::user();
            
            // Check permission for updating application
            if (!$user->hasPermissionTo('manage spb')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to update SPB application'
                ], 403);
            }

            $updateData = $request->validated();
            $updateData['reviewed_by'] = $user->id;
            $updateData['reviewed_at'] = now();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'spb', "update_application:{$applicationId}");

            $application = $this->spbService->updateApplicationStatus($applicationId, $updateData);

            return response()->json([
                'success' => true,
                'message' => 'SPB application status updated successfully',
                'data' => $application
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update SPB application status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Issue SPB certificate.
     */
    public function issueCertificate(Request $request, $applicationId): JsonResponse
    {
        $request->validate([
            'certificate_number' => 'required|string|unique:spb_certificates,number',
            'valid_until' => 'required|date|after:today',
            'conditions' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        try {
            $user = Auth::user();
            
            // Check permission for issuing certificate
            if (!$user->hasPermissionTo('manage spb')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to issue SPB certificate'
                ], 403);
            }

            $certificateData = $request->validated();
            $certificateData['issued_by'] = $user->id;
            $certificateData['issued_at'] = now();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'spb', "issue_certificate:{$applicationId}");

            $certificate = $this->spbService->issueCertificate($applicationId, $certificateData);

            return response()->json([
                'success' => true,
                'message' => 'SPB certificate issued successfully',
                'data' => $certificate
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to issue SPB certificate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SPB certificates.
     */
    public function getCertificates(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['page', 'limit', 'search', 'status', 'vessel_name', 'date_from', 'date_to']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'spb', 'certificates_list');

            $certificates = $this->spbService->getCertificates($params);

            return response()->json([
                'success' => true,
                'data' => $certificates
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get SPB certificates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify SPB certificate.
     */
    public function verifyCertificate(Request $request): JsonResponse
    {
        $request->validate([
            'certificate_number' => 'required|string',
        ]);

        try {
            $user = Auth::user();
            $certificateNumber = $request->input('certificate_number');
            
            // Log service access
            AuditLog::logServiceAccess($user, 'spb', "verify_certificate:{$certificateNumber}");

            $verification = $this->spbService->verifyCertificate($certificateNumber);

            return response()->json([
                'success' => true,
                'data' => $verification
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify SPB certificate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vessel tracking data.
     */
    public function getVesselTracking(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['vessel_id', 'certificate_number', 'date_from', 'date_to']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'spb', 'vessel_tracking');

            $tracking = $this->spbService->getVesselTracking($params);

            return response()->json([
                'success' => true,
                'data' => $tracking
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get vessel tracking data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics for SPB.
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'spb', 'dashboard_stats');

            $stats = $this->spbService->getDashboardStats();

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
     * Generate SPB report.
     */
    public function generateReport(Request $request): JsonResponse
    {
        $request->validate([
            'report_type' => 'required|string|in:applications,certificates,statistics',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'format' => 'nullable|string|in:pdf,excel,csv',
        ]);

        try {
            $user = Auth::user();
            
            // Check permission for generating reports
            if (!$user->hasPermissionTo('manage spb')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to generate reports'
                ], 403);
            }

            $reportParams = $request->validated();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'spb', 'generate_report');

            $report = $this->spbService->generateReport($reportParams);

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
     * Sync data with SPB service.
     */
    public function syncData(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check permission for data sync
            if (!$user->hasPermissionTo('manage spb')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to sync data'
                ], 403);
            }

            // Log service access
            AuditLog::logServiceAccess($user, 'spb', 'data_sync');

            $syncResult = $this->spbService->syncData();

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