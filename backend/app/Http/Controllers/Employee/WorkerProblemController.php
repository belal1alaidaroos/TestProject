<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\WorkerProblem;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WorkerProblemController extends Controller
{
    /**
     * Display a listing of worker problems.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = WorkerProblem::with(['worker', 'createdBy', 'approvedBy']);
            
            // Filter by worker
            if ($request->has('worker_id') && $request->worker_id !== '') {
                $query->where('worker_id', $request->worker_id);
            }
            
            // Filter by problem type
            if ($request->has('problem_type') && $request->problem_type !== '') {
                $query->where('problem_type', $request->problem_type);
            }
            
            // Filter by status
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }
            
            // Date range filter
            if ($request->has('date_from')) {
                $query->whereDate('date_reported', '>=', $request->date_from);
            }
            
            if ($request->has('date_to')) {
                $query->whereDate('date_reported', '<=', $request->date_to);
            }
            
            // Pagination
            $perPage = $request->get('per_page', 10);
            $problems = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $problems
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch worker problems', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch worker problems'
            ], 500);
        }
    }

    /**
     * Store a newly reported worker problem.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'worker_id' => 'required|uuid|exists:workers,id',
            'problem_type' => 'required|in:escape,refusal,non_compliance,misconduct,early_return',
            'description' => 'required|string|min:10',
            'date_reported' => 'required|date|before_or_equal:today',
        ]);

        DB::beginTransaction();
        
        try {
            // Check if worker exists and is in valid state
            $worker = Worker::findOrFail($validated['worker_id']);
            
            if (!in_array($worker->state, ['Contracted', 'Delivered'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Worker must be in Contracted or Delivered state to report problems'
                ], 400);
            }
            
            $validated['id'] = Str::uuid();
            $validated['status'] = 'Pending';
            $validated['created_by'] = auth()->id();
            
            $problem = WorkerProblem::create($validated);
            
            // Update worker status if necessary
            if ($validated['problem_type'] === 'escape') {
                $worker->update(['status' => 'Blocked']);
            }
            
            DB::commit();
            
            Log::info('Worker problem reported successfully', [
                'problem_id' => $problem->id,
                'worker_id' => $worker->id,
                'problem_type' => $validated['problem_type'],
                'reported_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Worker problem reported successfully',
                'data' => $problem->load(['worker', 'createdBy'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to report worker problem', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to report worker problem'
            ], 500);
        }
    }

    /**
     * Display the specified worker problem.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $problem = WorkerProblem::with([
                'worker',
                'createdBy',
                'approvedBy'
            ])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $problem
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Worker problem not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to fetch worker problem', [
                'problem_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch worker problem'
            ], 500);
        }
    }

    /**
     * Resolve a worker problem.
     */
    public function resolve(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'resolution_action' => 'required|in:Dismissal,Re-training,Escalation',
            'resolution_notes' => 'required|string|min:10',
        ]);

        DB::beginTransaction();
        
        try {
            $problem = WorkerProblem::findOrFail($id);
            
            // Check if problem can be resolved
            if ($problem->status !== 'Approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved problems can be resolved'
                ], 400);
            }
            
            $problem->update([
                'resolution_action' => $validated['resolution_action'],
                'resolution_notes' => $validated['resolution_notes'],
                'status' => 'Closed'
            ]);
            
            // Update worker based on resolution action
            $worker = $problem->worker;
            if ($validated['resolution_action'] === 'Dismissal') {
                $worker->update([
                    'status' => 'Inactive',
                    'state' => 'Returned'
                ]);
            }
            
            DB::commit();
            
            Log::info('Worker problem resolved', [
                'problem_id' => $problem->id,
                'resolution_action' => $validated['resolution_action'],
                'resolved_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Worker problem resolved successfully',
                'data' => $problem->load(['worker', 'createdBy', 'approvedBy'])
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Worker problem not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to resolve worker problem', [
                'problem_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve worker problem'
            ], 500);
        }
    }

    /**
     * Get problem statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $stats = [
                'total_problems' => WorkerProblem::count(),
                'pending_problems' => WorkerProblem::where('status', 'Pending')->count(),
                'approved_problems' => WorkerProblem::where('status', 'Approved')->count(),
                'closed_problems' => WorkerProblem::where('status', 'Closed')->count(),
                'problems_by_type' => WorkerProblem::selectRaw('problem_type, COUNT(*) as count')
                    ->groupBy('problem_type')
                    ->pluck('count', 'problem_type'),
                'recent_problems' => WorkerProblem::with(['worker', 'createdBy'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch problem statistics', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics'
            ], 500);
        }
    }
}