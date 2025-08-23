<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public function logCreate(string $entityName, string $entityId, array $newValues): void
    {
        $this->createLog($entityName, $entityId, 'create', null, $newValues);
    }

    public function logUpdate(string $entityName, string $entityId, array $oldValues, array $newValues): void
    {
        $this->createLog($entityName, $entityId, 'update', $oldValues, $newValues);
    }

    public function logDelete(string $entityName, string $entityId, array $oldValues): void
    {
        $this->createLog($entityName, $entityId, 'delete', $oldValues, null);
    }

    public function logStatusChange(string $entityName, string $entityId, string $oldStatus, string $newStatus): void
    {
        $this->createLog($entityName, $entityId, 'status_change', 
            ['status' => $oldStatus], 
            ['status' => $newStatus]
        );
    }

    public function logCustomAction(string $entityName, string $entityId, string $action, array $data = []): void
    {
        $this->createLog($entityName, $entityId, $action, null, $data);
    }

    private function createLog(string $entityName, string $entityId, string $actionType, ?array $oldValues, ?array $newValues): void
    {
        $userId = Auth::id();
        
        if (!$userId) {
            return; // Skip logging if no authenticated user
        }

        AuditLog::create([
            'entity_name' => $entityName,
            'entity_id' => $entityId,
            'action_type' => $actionType,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getEntityLogs(string $entityName, string $entityId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::byEntity($entityName, $entityId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getUserLogs(string $userId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::byUser($userId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getRecentLogs(int $days = 30, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::recent($days)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getLogsByAction(string $actionType, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::byAction($actionType)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getLogsByDateRange(string $startDate, string $endDate, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::whereBetween('created_at', [$startDate, $endDate])
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getActivitySummary(int $days = 30): array
    {
        $logs = AuditLog::recent($days)->get();

        $summary = [
            'total_actions' => $logs->count(),
            'actions_by_type' => $logs->groupBy('action_type')->map->count(),
            'actions_by_entity' => $logs->groupBy('entity_name')->map->count(),
            'actions_by_user' => $logs->groupBy('user_id')->map->count(),
            'recent_activity' => $logs->take(10)->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action_description' => $log->action_description,
                    'user_name' => $log->user->name ?? 'Unknown',
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'time_ago' => $log->created_at->diffForHumans(),
                ];
            }),
        ];

        return $summary;
    }

    public function exportLogs(string $startDate, string $endDate, string $format = 'csv'): string
    {
        $logs = AuditLog::whereBetween('created_at', [$startDate, $endDate])
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($format === 'csv') {
            return $this->exportToCsv($logs);
        }

        return $this->exportToJson($logs);
    }

    private function exportToCsv(\Illuminate\Database\Eloquent\Collection $logs): string
    {
        $headers = [
            'ID',
            'Entity Name',
            'Entity ID',
            'Action Type',
            'User',
            'IP Address',
            'Created At',
            'Old Values',
            'New Values',
        ];

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers);

        foreach ($logs as $log) {
            fputcsv($csv, [
                $log->id,
                $log->entity_name,
                $log->entity_id,
                $log->action_type,
                $log->user->name ?? 'Unknown',
                $log->ip_address,
                $log->created_at->format('Y-m-d H:i:s'),
                json_encode($log->old_values),
                json_encode($log->new_values),
            ]);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return $content;
    }

    private function exportToJson(\Illuminate\Database\Eloquent\Collection $logs): string
    {
        return $logs->toJson(JSON_PRETTY_PRINT);
    }

    public function cleanupOldLogs(int $daysToKeep = 365): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return AuditLog::where('created_at', '<', $cutoffDate)->delete();
    }
}