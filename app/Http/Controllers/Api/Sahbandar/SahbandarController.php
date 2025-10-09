<?php

namespace App\Http\Controllers\API\Sahbandar;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\Microservices\SahbandarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SahbandarController extends Controller
{
    protected $sahbandarService;

    public function __construct(SahbandarService $sahbandarService)
    {
        $this->sahbandarService = $sahbandarService;
        $this->middleware('auth:api');
        $this->middleware('permission:access sahbandar|manage sahbandar');
    }

    /**
     * Get user profile for Sahbandar service.
     */
    public function getUserProfile(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'sahbandar', 'user_profile');

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
                        return str_contains($permission->name, 'sahbandar');
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
     * Get vessel data from Sahbandar service.
     */
    public function getVessels(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['page', 'limit', 'search', 'status', 'port_id']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'sahbandar', 'vessels_list');

            $vessels = $this->sahbandarService->getVessels($params);

            return response()->json([
                'success' => true,
                'data' => $vessels
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get vessels data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific vessel details.
     */
    public function getVessel(Request $request, $vesselId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'sahbandar', "vessel_detail:{$vesselId}");

            $vessel = $this->sahbandarService->getVessel($vesselId);

            if (!$vessel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vessel not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $vessel
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get vessel details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get port data.
     */
    public function getPorts(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['page', 'limit', 'search', 'region']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'sahbandar', 'ports_list');

            $ports = $this->sahbandarService->getPorts($params);

            return response()->json([
                'success' => true,
                'data' => $ports
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get ports data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get port activities.
     */
    public function getPortActivities(Request $request, $portId): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['date_from', 'date_to', 'activity_type']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'sahbandar', "port_activities:{$portId}");

            $activities = $this->sahbandarService->getPortActivities($portId, $params);

            return response()->json([
                'success' => true,
                'data' => $activities
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get port activities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create vessel clearance.
     */
    public function createVesselClearance(Request $request): JsonResponse
    {
        $request->validate([
            'vessel_id' => 'required|string',
            'port_id' => 'required|string',
            'clearance_type' => 'required|string|in:arrival,departure',
            'documents' => 'required|array',
            'notes' => 'nullable|string',
        ]);

        try {
            $user = Auth::user();
            
            // Check permission for creating clearance
            if (!$user->hasPermissionTo('manage sahbandar')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to create clearance'
                ], 403);
            }

            $clearanceData = $request->validated();
            $clearanceData['created_by'] = $user->id;
            
            // Log service access
            AuditLog::logServiceAccess($user, 'sahbandar', 'create_clearance');

            $clearance = $this->sahbandarService->createVesselClearance($clearanceData);

            return response()->json([
                'success' => true,
                'message' => 'Vessel clearance created successfully',
                'data' => $clearance
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create vessel clearance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update vessel clearance status.
     */
    public function updateClearanceStatus(Request $request, $clearanceId): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:pending,approved,rejected,completed',
            'notes' => 'nullable|string',
        ]);

        try {
            $user = Auth::user();
            
            // Check permission for updating clearance
            if (!$user->hasPermissionTo('manage sahbandar')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to update clearance'
                ], 403);
            }

            $updateData = $request->validated();
            $updateData['updated_by'] = $user->id;
            
            // Log service access
            AuditLog::logServiceAccess($user, 'sahbandar', "update_clearance:{$clearanceId}");

            $clearance = $this->sahbandarService->updateClearanceStatus($clearanceId, $updateData);

            return response()->json([
                'success' => true,
                'message' => 'Clearance status updated successfully',
                'data' => $clearance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update clearance status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get clearance history.
     */
    public function getClearanceHistory(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['page', 'limit', 'vessel_id', 'port_id', 'status', 'date_from', 'date_to']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'sahbandar', 'clearance_history');

            $history = $this->sahbandarService->getClearanceHistory($params);

            return response()->json([
                'success' => true,
                'data' => $history
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get clearance history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics for Sahbandar.
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'sahbandar', 'dashboard_stats');

            $stats = $this->sahbandarService->getDashboardStats();

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
     * Sync data with Sahbandar service.
     */
    public function syncData(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check permission for data sync
            if (!$user->hasPermissionTo('manage sahbandar')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to sync data'
                ], 403);
            }

            // Log service access
            AuditLog::logServiceAccess($user, 'sahbandar', 'data_sync');

            $syncResult = $this->sahbandarService->syncData();

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