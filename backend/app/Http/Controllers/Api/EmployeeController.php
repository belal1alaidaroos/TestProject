<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\AppRole;
use App\Models\Worker;
use App\Models\Contract;
use App\Models\WorkerReservation;
use App\Models\Notification;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * Get employee dashboard statistics
     */
    public function getDashboardStats(Request $request)
    {
        $user = $request->user();
        
        // Verify user is an employee
        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only employees can access this portal.'
            ], 403);
        }

        try {
            $stats = [
                'total_workers_managed' => Worker::where('assigned_employee_id', $user->id)->count(),
                'active_contracts' => Contract::where('assigned_employee_id', $user->id)
                    ->whereIn('status', ['active', 'pending'])
                    ->count(),
                'pending_reservations' => WorkerReservation::where('assigned_employee_id', $user->id)
                    ->where('status', 'pending')
                    ->count(),
                'completed_tasks' => Contract::where('assigned_employee_id', $user->id)
                    ->where('status', 'completed')
                    ->count(),
                'recent_notifications' => Notification::where('user_id', $user->id)
                    ->where('is_read', false)
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get workers assigned to the employee
     */
    public function getAssignedWorkers(Request $request)
    {
        $user = $request->user();
        
        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only employees can access this portal.'
            ], 403);
        }

        try {
            $query = Worker::with(['nationality', 'profession', 'agency'])
                ->where('assigned_employee_id', $user->id);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('profession_id')) {
                $query->where('profession_id', $request->profession_id);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name_en', 'like', "%{$search}%")
                      ->orWhere('name_ar', 'like', "%{$search}%")
                      ->orWhere('passport_number', 'like', "%{$search}%");
                });
            }

            $workers = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $workers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assigned workers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific worker details
     */
    public function getWorker(Request $request, $workerId)
    {
        $user = $request->user();
        
        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only employees can access this portal.'
            ], 403);
        }

        try {
            $worker = Worker::with([
                'nationality', 
                'profession', 
                'agency',
                'reservations',
                'contracts',
                'problems'
            ])->where('id', $workerId)
              ->where('assigned_employee_id', $user->id)
              ->first();

            if (!$worker) {
                return response()->json([
                    'success' => false,
                    'message' => 'Worker not found or not assigned to you'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $worker
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch worker details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get contracts assigned to the employee
     */
    public function getAssignedContracts(Request $request)
    {
        $user = $request->user();
        
        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only employees can access this portal.'
            ], 403);
        }

        try {
            $query = Contract::with(['worker', 'customer', 'agency'])
                ->where('assigned_employee_id', $user->id);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('worker', function ($wq) use ($search) {
                        $wq->where('name_en', 'like', "%{$search}%")
                           ->orWhere('name_ar', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name_en', 'like', "%{$search}%")
                           ->orWhere('name_ar', 'like', "%{$search}%");
                    });
                });
            }

            $contracts = $query->orderBy('created_at', 'desc')
                              ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $contracts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assigned contracts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific contract details
     */
    public function getContract(Request $request, $contractId)
    {
        $user = $request->user();
        
        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only employees can access this portal.'
            ], 403);
        }

        try {
            $contract = Contract::with([
                'worker', 
                'customer', 
                'agency',
                'payments',
                'invoices'
            ])->where('id', $contractId)
              ->where('assigned_employee_id', $user->id)
              ->first();

            if (!$contract) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contract not found or not assigned to you'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $contract
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contract details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update contract status (employee can update status to in_progress, completed, etc.)
     */
    public function updateContractStatus(Request $request, $contractId)
    {
        $user = $request->user();
        
        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only employees can access this portal.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['in_progress', 'completed', 'on_hold', 'cancelled'])],
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $contract = Contract::where('id', $contractId)
                               ->where('assigned_employee_id', $user->id)
                               ->first();

            if (!$contract) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contract not found or not assigned to you'
                ], 404);
            }

            $oldStatus = $contract->status;
            $contract->update([
                'status' => $request->status,
                'employee_notes' => $request->notes,
                'status_updated_at' => now(),
                'status_updated_by' => $user->id
            ]);

            // Log the status change
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'contract_status_updated',
                'table_name' => 'contracts',
                'record_id' => $contract->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => $request->status, 'notes' => $request->notes],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contract status updated successfully',
                'data' => $contract
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contract status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reservations assigned to the employee
     */
    public function getAssignedReservations(Request $request)
    {
        $user = $request->user();
        
        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only employees can access this portal.'
            ], 403);
        }

        try {
            $query = WorkerReservation::with(['worker', 'customer'])
                ->where('assigned_employee_id', $user->id);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $reservations = $query->orderBy('created_at', 'desc')
                                 ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $reservations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assigned reservations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update reservation status
     */
    public function updateReservationStatus(Request $request, $reservationId)
    {
        $user = $request->user();
        
        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only employees can access this portal.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['confirmed', 'rejected', 'cancelled'])],
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reservation = WorkerReservation::where('id', $reservationId)
                                           ->where('assigned_employee_id', $user->id)
                                           ->first();

            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservation not found or not assigned to you'
                ], 404);
            }

            $oldStatus = $reservation->status;
            $reservation->update([
                'status' => $request->status,
                'employee_notes' => $request->notes,
                'status_updated_at' => now(),
                'status_updated_by' => $user->id
            ]);

            // Log the status change
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'reservation_status_updated',
                'table_name' => 'worker_reservations',
                'record_id' => $reservation->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => $request->status, 'notes' => $request->notes],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reservation status updated successfully',
                'data' => $reservation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reservation status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee's notifications
     */
    public function getNotifications(Request $request)
    {
        $user = $request->user();
        
        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only employees can access this portal.'
            ], 403);
        }

        try {
            $query = Notification::where('user_id', $user->id);

            if ($request->has('is_read')) {
                $query->where('is_read', $request->boolean('is_read'));
            }

            $notifications = $query->orderBy('created_at', 'desc')
                                  ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $notifications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead(Request $request, $notificationId)
    {
        $user = $request->user();
        
        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only employees can access this portal.'
            ], 403);
        }

        try {
            $notification = Notification::where('id', $notificationId)
                                       ->where('user_id', $user->id)
                                       ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->update([
                'is_read' => true,
                'read_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee's audit logs
     */
    public function getAuditLogs(Request $request)
    {
        $user = $request->user();
        
        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only employees can access this portal.'
            ], 403);
        }

        try {
            $query = AuditLog::where('user_id', $user->id);

            if ($request->has('action')) {
                $query->where('action', $request->action);
            }

            if ($request->has('table_name')) {
                $query->where('table_name', $request->table_name);
            }

            $logs = $query->orderBy('created_at', 'desc')
                         ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch audit logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee's assigned tasks summary
     */
    public function getTasksSummary(Request $request)
    {
        $user = $request->user();
        
        if ($user->user_type !== 'Internal') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only employees can access this portal.'
            ], 403);
        }

        try {
            $summary = [
                'workers' => [
                    'total' => Worker::where('assigned_employee_id', $user->id)->count(),
                    'active' => Worker::where('assigned_employee_id', $user->id)
                        ->where('status', 'active')->count(),
                    'inactive' => Worker::where('assigned_employee_id', $user->id)
                        ->where('status', 'inactive')->count(),
                ],
                'contracts' => [
                    'total' => Contract::where('assigned_employee_id', $user->id)->count(),
                    'active' => Contract::where('assigned_employee_id', $user->id)
                        ->where('status', 'active')->count(),
                    'pending' => Contract::where('assigned_employee_id', $user->id)
                        ->where('status', 'pending')->count(),
                    'completed' => Contract::where('assigned_employee_id', $user->id)
                        ->where('status', 'completed')->count(),
                ],
                'reservations' => [
                    'total' => WorkerReservation::where('assigned_employee_id', $user->id)->count(),
                    'pending' => WorkerReservation::where('assigned_employee_id', $user->id)
                        ->where('status', 'pending')->count(),
                    'confirmed' => WorkerReservation::where('assigned_employee_id', $user->id)
                        ->where('status', 'confirmed')->count(),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tasks summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}