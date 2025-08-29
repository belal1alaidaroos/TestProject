<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Worker;
use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WorkerController extends Controller
{
    /**
     * Display a listing of workers.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Worker::with(['nationality', 'profession', 'agency']);
            
            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name_en', 'like', "%{$search}%")
                      ->orWhere('name_ar', 'like', "%{$search}%")
                      ->orWhere('passport_number', 'like', "%{$search}%");
                });
            }
            
            // Filter by status
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }
            
            // Filter by profession
            if ($request->has('profession_id') && $request->profession_id !== '') {
                $query->where('profession_id', $request->profession_id);
            }
            
            // Filter by nationality
            if ($request->has('nationality_id') && $request->nationality_id !== '') {
                $query->where('nationality_id', $request->nationality_id);
            }
            
            // Pagination
            $perPage = $request->get('per_page', 10);
            $workers = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $workers
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch workers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch workers'
            ], 500);
        }
    }

    /**
     * Store a newly created worker.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'passport_number' => 'required|string|unique:workers,passport_number',
            'date_of_birth' => 'required|date|before:today',
            'nationality_id' => 'required|uuid|exists:nationalities,id',
            'profession_id' => 'required|uuid|exists:professions,id',
            'agency_id' => 'required|uuid|exists:agencies,id',
            'gender' => 'required|in:Male,Female',
            'experience_years' => 'required|integer|min:0',
            'education_level' => 'required|in:Primary,Secondary,Bachelor,Master,Doctorate',
            'marital_status' => 'required|in:Single,Married,Divorced,Widowed',
            'languages_spoken' => 'nullable|array',
            'monthly_salary' => 'required|numeric|min:0',
            'contract_duration' => 'required|integer|min:1',
            'medical_status' => 'required|in:Fit,Unfit,Pending',
            'skills' => 'nullable|array',
        ]);

        DB::beginTransaction();
        
        try {
            $validated['id'] = Str::uuid();
            $validated['state'] = 'Inventory';
            $validated['created_by'] = auth()->id();
            
            $worker = Worker::create($validated);
            
            DB::commit();
            
            Log::info('Worker created successfully', [
                'worker_id' => $worker->id,
                'created_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Worker created successfully',
                'data' => $worker->load(['nationality', 'profession', 'agency'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create worker', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create worker'
            ], 500);
        }
    }

    /**
     * Display the specified worker.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $worker = Worker::with([
                'nationality', 
                'profession', 
                'agency',
                'contracts',
                'problems'
            ])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $worker
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Worker not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to fetch worker', [
                'worker_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch worker'
            ], 500);
        }
    }

    /**
     * Update the specified worker.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name_en' => 'sometimes|string|max:255',
            'name_ar' => 'sometimes|string|max:255',
            'passport_number' => 'sometimes|string|unique:workers,passport_number,' . $id,
            'date_of_birth' => 'sometimes|date|before:today',
            'nationality_id' => 'sometimes|uuid|exists:nationalities,id',
            'profession_id' => 'sometimes|uuid|exists:professions,id',
            'agency_id' => 'sometimes|uuid|exists:agencies,id',
            'gender' => 'sometimes|in:Male,Female',
            'experience_years' => 'sometimes|integer|min:0',
            'education_level' => 'sometimes|in:Primary,Secondary,Bachelor,Master,Doctorate',
            'marital_status' => 'sometimes|in:Single,Married,Divorced,Widowed',
            'languages_spoken' => 'sometimes|array',
            'monthly_salary' => 'sometimes|numeric|min:0',
            'contract_duration' => 'sometimes|integer|min:1',
            'medical_status' => 'sometimes|in:Fit,Unfit,Pending',
            'skills' => 'sometimes|array',
            'status' => 'sometimes|in:Active,Inactive,Blocked',
        ]);

        DB::beginTransaction();
        
        try {
            $worker = Worker::findOrFail($id);
            
            // Check if worker can be updated based on state
            if (in_array($worker->state, ['Contracted', 'UnderProcessing', 'Delivered'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update worker in current state: ' . $worker->state
                ], 400);
            }
            
            $worker->update($validated);
            
            DB::commit();
            
            Log::info('Worker updated successfully', [
                'worker_id' => $worker->id,
                'updated_by' => auth()->id(),
                'changes' => $validated
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Worker updated successfully',
                'data' => $worker->load(['nationality', 'profession', 'agency'])
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Worker not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update worker', [
                'worker_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update worker'
            ], 500);
        }
    }

    /**
     * Remove the specified worker.
     */
    public function destroy(string $id): JsonResponse
    {
        DB::beginTransaction();
        
        try {
            $worker = Worker::findOrFail($id);
            
            // Check if worker can be deleted
            if ($worker->state !== 'Inventory') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete worker in current state: ' . $worker->state
                ], 400);
            }
            
            // Check for existing contracts
            if ($worker->contracts()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete worker with existing contracts'
                ], 400);
            }
            
            $worker->delete();
            
            DB::commit();
            
            Log::info('Worker deleted successfully', [
                'worker_id' => $id,
                'deleted_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Worker deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Worker not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete worker', [
                'worker_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete worker'
            ], 500);
        }
    }
}