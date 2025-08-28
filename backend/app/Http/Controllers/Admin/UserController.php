<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::with(['roles', 'agency']);

            // Apply filters
            if ($request->has('user_type')) {
                $query->where('user_type', $request->user_type);
            }

            if ($request->has('role_id')) {
                $query->whereHas('roles', function($q) use ($request) {
                    $q->where('roles.id', $request->role_id);
                });
            }

            if ($request->has('agency_id')) {
                $query->where('agency_id', $request->agency_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Search by name, email, or phone
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // Sort by
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $users = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 200,
                'message' => 'Users retrieved successfully',
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user): JsonResponse
    {
        try {
            $user->load(['roles', 'agency', 'permissions']);

            return response()->json([
                'status' => 200,
                'message' => 'User details retrieved successfully',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving user details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'required|string|max:20|unique:users,phone',
                'user_type' => 'required|in:customer,agency,admin,internal',
                'agency_id' => 'nullable|required_if:user_type,agency|exists:agencies,id',
                'password' => 'required|string|min:8|confirmed',
                'roles' => 'required|array|min:1',
                'roles.*' => 'exists:roles,id',
                'is_active' => 'boolean',
                'status' => 'in:active,inactive,suspended'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Create user
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'user_type' => $request->user_type,
                    'agency_id' => $request->agency_id,
                    'password' => Hash::make($request->password),
                    'is_active' => $request->get('is_active', true),
                    'status' => $request->get('status', 'active')
                ]);

                // Assign roles
                $user->roles()->attach($request->roles);

                DB::commit();

                $user->load(['roles', 'agency']);

                return response()->json([
                    'status' => 201,
                    'message' => 'User created successfully',
                    'data' => $user
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
                'phone' => 'sometimes|required|string|max:20|unique:users,phone,' . $user->id,
                'user_type' => 'sometimes|required|in:customer,agency,admin,internal',
                'agency_id' => 'nullable|required_if:user_type,agency|exists:agencies,id',
                'password' => 'nullable|string|min:8|confirmed',
                'roles' => 'sometimes|required|array|min:1',
                'roles.*' => 'exists:roles,id',
                'is_active' => 'boolean',
                'status' => 'in:active,inactive,suspended'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Update user data
                $updateData = $request->only(['name', 'email', 'phone', 'user_type', 'agency_id', 'is_active', 'status']);
                
                if ($request->filled('password')) {
                    $updateData['password'] = Hash::make($request->password);
                }

                $user->update($updateData);

                // Update roles if provided
                if ($request->has('roles')) {
                    $user->roles()->sync($request->roles);
                }

                DB::commit();

                $user->load(['roles', 'agency']);

                return response()->json([
                    'status' => 200,
                    'message' => 'User updated successfully',
                    'data' => $user
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error updating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            // Check if user has related data
            if ($user->user_type === 'customer' && $user->reservations()->exists()) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Cannot delete user with existing reservations'
                ], 400);
            }

            if ($user->user_type === 'agency' && $user->workers()->exists()) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Cannot delete user with existing workers'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Remove roles
                $user->roles()->detach();

                // Delete user
                $user->delete();

                DB::commit();

                return response()->json([
                    'status' => 200,
                    'message' => 'User deleted successfully'
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error deleting user: ' . $e->getMessage()
            ], 500);
        }
    }
}