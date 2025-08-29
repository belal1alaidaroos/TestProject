<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContractController extends Controller
{
    /**
     * Display a listing of contracts.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Contract::with([
                'customer',
                'worker',
                'package',
                'invoice'
            ]);
            
            // Search by contract number or customer name
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('contract_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function ($q) use ($search) {
                          $q->where('name_en', 'like', "%{$search}%")
                            ->orWhere('name_ar', 'like', "%{$search}%");
                      });
                });
            }
            
            // Filter by status
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }
            
            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('start_date', '>=', $request->date_from);
            }
            
            if ($request->has('date_to')) {
                $query->whereDate('end_date', '<=', $request->date_to);
            }
            
            // Pagination
            $perPage = $request->get('per_page', 10);
            $contracts = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $contracts
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch contracts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contracts'
            ], 500);
        }
    }

    /**
     * Display the specified contract.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $contract = Contract::with([
                'customer',
                'worker',
                'package',
                'invoice',
                'payments'
            ])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $contract
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to fetch contract', [
                'contract_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contract'
            ], 500);
        }
    }

    /**
     * Update contract status.
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:Active,Suspended,Terminated,Completed',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        
        try {
            $contract = Contract::findOrFail($id);
            
            // Validate status transition
            $validTransitions = [
                'Draft' => ['Active', 'Cancelled'],
                'Active' => ['Suspended', 'Terminated', 'Completed'],
                'Suspended' => ['Active', 'Terminated'],
                'Terminated' => [],
                'Completed' => [],
                'Cancelled' => []
            ];
            
            if (!in_array($validated['status'], $validTransitions[$contract->status] ?? [])) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot transition from {$contract->status} to {$validated['status']}"
                ], 400);
            }
            
            $previousStatus = $contract->status;
            $contract->update(['status' => $validated['status']]);
            
            // Update worker state based on contract status
            $worker = $contract->worker;
            switch ($validated['status']) {
                case 'Active':
                    $worker->update(['state' => 'Delivered']);
                    break;
                case 'Terminated':
                case 'Completed':
                    $worker->update(['state' => 'Returned']);
                    break;
            }
            
            // Log the status change
            Log::info('Contract status updated', [
                'contract_id' => $contract->id,
                'previous_status' => $previousStatus,
                'new_status' => $validated['status'],
                'updated_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Contract status updated successfully',
                'data' => $contract->load(['customer', 'worker', 'package'])
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Contract not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update contract status', [
                'contract_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contract status'
            ], 500);
        }
    }

    /**
     * Get contract statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $stats = [
                'total_contracts' => Contract::count(),
                'active_contracts' => Contract::where('status', 'Active')->count(),
                'suspended_contracts' => Contract::where('status', 'Suspended')->count(),
                'completed_contracts' => Contract::where('status', 'Completed')->count(),
                'contracts_by_month' => Contract::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                    ->whereYear('created_at', date('Y'))
                    ->groupBy('month')
                    ->pluck('count', 'month'),
                'revenue_by_status' => Contract::selectRaw('status, SUM(total_amount) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status'),
                'expiring_soon' => Contract::where('status', 'Active')
                    ->whereDate('end_date', '<=', now()->addDays(30))
                    ->count()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch contract statistics', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics'
            ], 500);
        }
    }
}