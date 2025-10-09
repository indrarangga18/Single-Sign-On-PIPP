<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Carbon\Carbon;

class LogsController extends Controller
{
    protected LoggingService $loggingService;

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * Search logs with various filters
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'level' => 'sometimes|in:emergency,alert,critical,error,warning,notice,info,debug',
            'channel' => 'sometimes|in:security,auth,api,sso,microservices,performance,database,cache,errors,monitoring,audit',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'search' => 'sometimes|string|max:255',
            'user_id' => 'sometimes|integer',
            'ip_address' => 'sometimes|ip',
            'limit' => 'sometimes|integer|min:1|max:1000',
            'offset' => 'sometimes|integer|min:0'
        ]);

        $criteria = [
            'level' => $request->input('level'),
            'channel' => $request->input('channel'),
            'start_date' => $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null,
            'end_date' => $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null,
            'search' => $request->input('search'),
            'user_id' => $request->input('user_id'),
            'ip_address' => $request->input('ip_address'),
            'limit' => $request->input('limit', 100),
            'offset' => $request->input('offset', 0)
        ];

        $logs = $this->loggingService->searchLogs($criteria);

        return response()->json([
            'logs' => $logs,
            'criteria' => array_filter($criteria),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get log statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:1h,24h,7d,30d',
            'group_by' => 'sometimes|in:level,channel,hour,day'
        ]);

        $period = $request->input('period', '24h');
        $groupBy = $request->input('group_by', 'level');

        $stats = $this->loggingService->getLogStatistics($period, $groupBy);

        return response()->json([
            'statistics' => $stats,
            'period' => $period,
            'group_by' => $groupBy,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Export logs in various formats
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:json,csv,txt',
            'level' => 'sometimes|in:emergency,alert,critical,error,warning,notice,info,debug',
            'channel' => 'sometimes|in:security,auth,api,sso,microservices,performance,database,cache,errors,monitoring,audit',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'search' => 'sometimes|string|max:255',
            'limit' => 'sometimes|integer|min:1|max:10000'
        ]);

        $format = $request->input('format');
        $criteria = [
            'level' => $request->input('level'),
            'channel' => $request->input('channel'),
            'start_date' => $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null,
            'end_date' => $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null,
            'search' => $request->input('search'),
            'limit' => $request->input('limit', 1000)
        ];

        $exportData = $this->loggingService->exportLogs($criteria, $format);

        $filename = 'logs_' . now()->format('Y-m-d_H-i-s') . '.' . $format;

        switch ($format) {
            case 'json':
                return response()->json($exportData)
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
            
            case 'csv':
                return response($exportData, 200, [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                ]);
            
            case 'txt':
                return response($exportData, 200, [
                    'Content-Type' => 'text/plain',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                ]);
            
            default:
                return response()->json(['error' => 'Unsupported format'], 400);
        }
    }

    /**
     * Get security-specific logs
     */
    public function securityLogs(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'event_type' => 'sometimes|in:login_attempt,brute_force,suspicious_activity,xss_attempt,sql_injection,rate_limit_exceeded,account_lockout',
            'severity' => 'sometimes|in:low,medium,high,critical',
            'ip_address' => 'sometimes|ip',
            'user_id' => 'sometimes|integer',
            'limit' => 'sometimes|integer|min:1|max:1000'
        ]);

        $criteria = [
            'channel' => 'security',
            'start_date' => $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null,
            'end_date' => $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null,
            'event_type' => $request->input('event_type'),
            'severity' => $request->input('severity'),
            'ip_address' => $request->input('ip_address'),
            'user_id' => $request->input('user_id'),
            'limit' => $request->input('limit', 100)
        ];

        $logs = $this->loggingService->searchLogs($criteria);
        $stats = $this->getSecurityLogStats($logs);

        return response()->json([
            'security_logs' => $logs,
            'statistics' => $stats,
            'criteria' => array_filter($criteria),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get audit-specific logs
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'action' => 'sometimes|string|max:100',
            'model_type' => 'sometimes|string|max:100',
            'user_id' => 'sometimes|integer',
            'ip_address' => 'sometimes|ip',
            'limit' => 'sometimes|integer|min:1|max:1000'
        ]);

        $criteria = [
            'channel' => 'audit',
            'start_date' => $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null,
            'end_date' => $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null,
            'action' => $request->input('action'),
            'model_type' => $request->input('model_type'),
            'user_id' => $request->input('user_id'),
            'ip_address' => $request->input('ip_address'),
            'limit' => $request->input('limit', 100)
        ];

        $logs = $this->loggingService->searchLogs($criteria);
        $stats = $this->getAuditLogStats($logs);

        return response()->json([
            'audit_logs' => $logs,
            'statistics' => $stats,
            'criteria' => array_filter($criteria),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get performance-specific logs
     */
    public function performanceLogs(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'metric_type' => 'sometimes|in:api_response_time,database_query_time,cache_operation,memory_usage',
            'threshold' => 'sometimes|numeric|min:0',
            'endpoint' => 'sometimes|string|max:255',
            'limit' => 'sometimes|integer|min:1|max:1000'
        ]);

        $criteria = [
            'channel' => 'performance',
            'start_date' => $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null,
            'end_date' => $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null,
            'metric_type' => $request->input('metric_type'),
            'threshold' => $request->input('threshold'),
            'endpoint' => $request->input('endpoint'),
            'limit' => $request->input('limit', 100)
        ];

        $logs = $this->loggingService->searchLogs($criteria);
        $stats = $this->getPerformanceLogStats($logs);

        return response()->json([
            'performance_logs' => $logs,
            'statistics' => $stats,
            'criteria' => array_filter($criteria),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get real-time log stream (for monitoring dashboards)
     */
    public function stream(Request $request): JsonResponse
    {
        $request->validate([
            'channels' => 'sometimes|array',
            'channels.*' => 'in:security,auth,api,sso,microservices,performance,database,cache,errors,monitoring,audit',
            'levels' => 'sometimes|array',
            'levels.*' => 'in:emergency,alert,critical,error,warning,notice,info,debug',
            'last_timestamp' => 'sometimes|date'
        ]);

        $channels = $request->input('channels', ['security', 'auth', 'api']);
        $levels = $request->input('levels', ['error', 'warning', 'critical', 'emergency']);
        $lastTimestamp = $request->input('last_timestamp') ? Carbon::parse($request->input('last_timestamp')) : Carbon::now()->subMinutes(5);

        $criteria = [
            'channels' => $channels,
            'levels' => $levels,
            'start_date' => $lastTimestamp,
            'limit' => 50
        ];

        $logs = $this->loggingService->searchLogs($criteria);

        return response()->json([
            'logs' => $logs,
            'last_timestamp' => now()->toISOString(),
            'criteria' => $criteria
        ]);
    }

    /**
     * Get security log statistics
     */
    private function getSecurityLogStats(array $logs): array
    {
        $stats = [
            'total_events' => count($logs),
            'by_severity' => [],
            'by_event_type' => [],
            'unique_ips' => [],
            'top_threats' => []
        ];

        foreach ($logs as $log) {
            $context = $log['context'] ?? [];
            
            // Count by severity
            $severity = $context['severity'] ?? 'unknown';
            $stats['by_severity'][$severity] = ($stats['by_severity'][$severity] ?? 0) + 1;
            
            // Count by event type
            $eventType = $context['event_type'] ?? 'unknown';
            $stats['by_event_type'][$eventType] = ($stats['by_event_type'][$eventType] ?? 0) + 1;
            
            // Collect unique IPs
            if (isset($context['ip_address'])) {
                $stats['unique_ips'][] = $context['ip_address'];
            }
        }

        $stats['unique_ips'] = array_unique($stats['unique_ips']);
        $stats['unique_ip_count'] = count($stats['unique_ips']);

        return $stats;
    }

    /**
     * Get audit log statistics
     */
    private function getAuditLogStats(array $logs): array
    {
        $stats = [
            'total_events' => count($logs),
            'by_action' => [],
            'by_model_type' => [],
            'by_user' => [],
            'timeline' => []
        ];

        foreach ($logs as $log) {
            $context = $log['context'] ?? [];
            
            // Count by action
            $action = $context['action'] ?? 'unknown';
            $stats['by_action'][$action] = ($stats['by_action'][$action] ?? 0) + 1;
            
            // Count by model type
            $modelType = $context['model_type'] ?? 'unknown';
            $stats['by_model_type'][$modelType] = ($stats['by_model_type'][$modelType] ?? 0) + 1;
            
            // Count by user
            $userId = $context['user_id'] ?? 'system';
            $stats['by_user'][$userId] = ($stats['by_user'][$userId] ?? 0) + 1;
        }

        return $stats;
    }

    /**
     * Get performance log statistics
     */
    private function getPerformanceLogStats(array $logs): array
    {
        $stats = [
            'total_events' => count($logs),
            'by_metric_type' => [],
            'average_response_time' => 0,
            'slowest_endpoints' => [],
            'performance_trends' => []
        ];

        $responseTimes = [];

        foreach ($logs as $log) {
            $context = $log['context'] ?? [];
            
            // Count by metric type
            $metricType = $context['metric_type'] ?? 'unknown';
            $stats['by_metric_type'][$metricType] = ($stats['by_metric_type'][$metricType] ?? 0) + 1;
            
            // Collect response times
            if (isset($context['response_time'])) {
                $responseTimes[] = $context['response_time'];
            }
        }

        if (!empty($responseTimes)) {
            $stats['average_response_time'] = array_sum($responseTimes) / count($responseTimes);
            $stats['max_response_time'] = max($responseTimes);
            $stats['min_response_time'] = min($responseTimes);
        }

        return $stats;
    }
}