<?php

namespace App\Http\Controllers\API\EPIT;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\Microservices\EPITService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EPITController extends Controller
{
    protected $epitService;

    public function __construct(EPITService $epitService)
    {
        $this->epitService = $epitService;
        $this->middleware('auth:api');
        $this->middleware('permission:access epit|manage epit');
    }

    /**
     * Get user profile for EPIT service.
     */
    public function getUserProfile(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'epit', 'user_profile');

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
                        return str_contains($permission->name, 'epit');
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
     * Get port information systems.
     */
    public function getPortSystems(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['page', 'limit', 'search', 'port_code', 'system_type', 'status']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'epit', 'port_systems_list');

            $systems = $this->epitService->getPortSystems($params);

            return response()->json([
                'success' => true,
                'data' => $systems
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get port systems',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific port system details.
     */
    public function getPortSystem(Request $request, $systemId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'epit', "port_system_detail:{$systemId}");

            $system = $this->epitService->getPortSystem($systemId);

            if (!$system) {
                return response()->json([
                    'success' => false,
                    'message' => 'Port system not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $system
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get port system details',
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
            $params = $request->only(['vessel_id', 'vessel_name', 'imo_number', 'port_code', 'status', 'date_from', 'date_to']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'epit', 'vessel_tracking');

            $tracking = $this->epitService->getVesselTracking($params);

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
     * Get port operations data.
     */
    public function getPortOperations(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['port_code', 'operation_type', 'date_from', 'date_to', 'status', 'page', 'limit']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'epit', 'port_operations');

            $operations = $this->epitService->getPortOperations($params);

            return response()->json([
                'success' => true,
                'data' => $operations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get port operations data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new port operation record.
     */
    public function createPortOperation(Request $request): JsonResponse
    {
        $request->validate([
            'port_code' => 'required|string|max:10',
            'vessel_id' => 'required|string',
            'vessel_name' => 'required|string|max:255',
            'imo_number' => 'nullable|string|max:20',
            'operation_type' => 'required|string|in:arrival,departure,berthing,unberthing,loading,unloading',
            'berth_number' => 'nullable|string|max:20',
            'scheduled_time' => 'required|date',
            'actual_time' => 'nullable|date',
            'cargo_type' => 'nullable|string',
            'cargo_quantity' => 'nullable|numeric|min:0',
            'cargo_unit' => 'nullable|string|in:ton,m3,teu,pieces',
            'agent_name' => 'nullable|string|max:255',
            'captain_name' => 'nullable|string|max:255',
            'crew_count' => 'nullable|integer|min:0',
            'passenger_count' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $user = Auth::user();
            
            // Check permission for creating operation
            if (!$user->hasPermissionTo('manage epit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to create port operation'
                ], 403);
            }

            $operationData = $request->validated();
            $operationData['operator_id'] = $user->id;
            $operationData['status'] = 'scheduled';
            
            // Log service access
            AuditLog::logServiceAccess($user, 'epit', 'create_port_operation');

            $operation = $this->epitService->createPortOperation($operationData);

            return response()->json([
                'success' => true,
                'message' => 'Port operation created successfully',
                'data' => $operation
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create port operation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update port operation status.
     */
    public function updateOperationStatus(Request $request, $operationId): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:scheduled,in_progress,completed,cancelled,delayed',
            'actual_time' => 'nullable|date',
            'completion_notes' => 'nullable|string',
            'delay_reason' => 'nullable|string',
        ]);

        try {
            $user = Auth::user();
            
            // Check permission for updating operation
            if (!$user->hasPermissionTo('manage epit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to update port operation'
                ], 403);
            }

            $updateData = $request->validated();
            $updateData['updated_by'] = $user->id;
            $updateData['updated_at'] = now();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'epit', "update_operation:{$operationId}");

            $operation = $this->epitService->updateOperationStatus($operationId, $updateData);

            return response()->json([
                'success' => true,
                'message' => 'Port operation status updated successfully',
                'data' => $operation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update port operation status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get berth availability.
     */
    public function getBerthAvailability(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['port_code', 'berth_type', 'date_from', 'date_to']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'epit', 'berth_availability');

            $availability = $this->epitService->getBerthAvailability($params);

            return response()->json([
                'success' => true,
                'data' => $availability
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get berth availability',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cargo statistics.
     */
    public function getCargoStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['port_code', 'cargo_type', 'date_from', 'date_to', 'group_by']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'epit', 'cargo_statistics');

            $statistics = $this->epitService->getCargoStatistics($params);

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get cargo statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get port performance metrics.
     */
    public function getPortPerformance(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['port_code', 'metric_type', 'date_from', 'date_to']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'epit', 'port_performance');

            $performance = $this->epitService->getPortPerformance($params);

            return response()->json([
                'success' => true,
                'data' => $performance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get port performance metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get weather and sea conditions.
     */
    public function getWeatherConditions(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['port_code', 'date_from', 'date_to']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'epit', 'weather_conditions');

            $conditions = $this->epitService->getWeatherConditions($params);

            return response()->json([
                'success' => true,
                'data' => $conditions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get weather conditions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get port facilities information.
     */
    public function getPortFacilities(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['port_code', 'facility_type', 'status']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'epit', 'port_facilities');

            $facilities = $this->epitService->getPortFacilities($params);

            return response()->json([
                'success' => true,
                'data' => $facilities
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get port facilities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vessel schedules.
     */
    public function getVesselSchedules(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $params = $request->only(['port_code', 'vessel_type', 'date_from', 'date_to', 'status']);
            
            // Log service access
            AuditLog::logServiceAccess($user, 'epit', 'vessel_schedules');

            $schedules = $this->epitService->getVesselSchedules($params);

            return response()->json([
                'success' => true,
                'data' => $schedules
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get vessel schedules',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics for EPIT.
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'epit', 'dashboard_stats');

            $stats = $this->epitService->getDashboardStats();

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
     * Generate EPIT report.
     */
    public function generateReport(Request $request): JsonResponse
    {
        $request->validate([
            'report_type' => 'required|string|in:port_operations,vessel_traffic,cargo_throughput,performance_metrics',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'port_code' => 'nullable|string',
            'format' => 'nullable|string|in:pdf,excel,csv',
        ]);

        try {
            $user = Auth::user();
            
            // Check permission for generating reports
            if (!$user->hasPermissionTo('manage epit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to generate reports'
                ], 403);
            }

            $reportParams = $request->validated();
            
            // Log service access
            AuditLog::logServiceAccess($user, 'epit', 'generate_report');

            $report = $this->epitService->generateReport($reportParams);

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
     * Sync data with EPIT service.
     */
    public function syncData(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check permission for data sync
            if (!$user->hasPermissionTo('manage epit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to sync data'
                ], 403);
            }

            // Log service access
            AuditLog::logServiceAccess($user, 'epit', 'data_sync');

            $syncResult = $this->epitService->syncData();

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

    /**
     * Get real-time port status.
     */
    public function getPortStatus(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $portCode = $request->input('port_code');
            
            // Log service access
            AuditLog::logServiceAccess($user, 'epit', 'port_status');

            $status = $this->epitService->getPortStatus($portCode);

            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get port status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}