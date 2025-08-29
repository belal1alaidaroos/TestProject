<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\WorkerReservation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    /**
     * Display a listing of reservations.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = WorkerReservation::with([
                'customer',
                'worker' => function ($q) {
                    $q->with(['nationality', 'profession', 'agency']);
                }
            ]);
            
            // Search by customer name
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('customer', function ($q) use ($search) {
                    $q->where('name_en', 'like', "%{$search}%")
                      ->orWhere('name_ar', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
            
            // Filter by state
            if ($request->has('state') && $request->state !== '') {
                $query->where('state', $request->state);
            }
            
            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            
            // Pagination
            $perPage = $request->get('per_page', 10);
            $reservations = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $reservations
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch reservations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reservations'
            ], 500);
        }
    }

    /**
     * Display the specified reservation.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $reservation = WorkerReservation::with([
                'customer',
                'worker' => function ($q) {
                    $q->with(['nationality', 'profession', 'agency']);
                }
            ])->findOrFail($id);
            
            // Calculate remaining time if active
            if ($reservation->state === 'Reserved') {
                $expiresAt = \Carbon\Carbon::parse($reservation->expires_at);
                $reservation->remaining_minutes = max(0, now()->diffInMinutes($expiresAt, false));
            }
            
            return response()->json([
                'success' => true,
                'data' => $reservation
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to fetch reservation', [
                'reservation_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reservation'
            ], 500);
        }
    }

    /**
     * Process a reservation (approve/reject).
     */
    public function process(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject,extend',
            'notes' => 'nullable|string',
            'extension_minutes' => 'required_if:action,extend|integer|min:15|max:120'
        ]);

        DB::beginTransaction();
        
        try {
            $reservation = WorkerReservation::findOrFail($id);
            
            // Check if reservation can be processed
            if ($reservation->state !== 'Reserved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active reservations can be processed'
                ], 400);
            }
            
            // Check if reservation has expired
            if (now()->greaterThan($reservation->expires_at)) {
                $reservation->update(['state' => 'Expired']);
                DB::commit();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Reservation has expired'
                ], 400);
            }
            
            switch ($validated['action']) {
                case 'approve':
                    // This would typically trigger contract creation workflow
                    $reservation->update(['state' => 'Confirmed']);
                    $message = 'Reservation approved successfully';
                    break;
                    
                case 'reject':
                    $reservation->update(['state' => 'Cancelled']);
                    // Release the worker
                    $reservation->worker->update(['state' => 'Available']);
                    $message = 'Reservation rejected successfully';
                    break;
                    
                case 'extend':
                    $newExpiry = now()->addMinutes($validated['extension_minutes']);
                    $reservation->update(['expires_at' => $newExpiry]);
                    $message = "Reservation extended by {$validated['extension_minutes']} minutes";
                    break;
            }
            
            // Log the action
            Log::info('Reservation processed', [
                'reservation_id' => $reservation->id,
                'action' => $validated['action'],
                'processed_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $reservation->fresh()->load(['customer', 'worker'])
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Reservation not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to process reservation', [
                'reservation_id' => $id,
                'action' => $validated['action'],
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process reservation'
            ], 500);
        }
    }

    /**
     * Get reservation statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $stats = [
                'total_reservations' => WorkerReservation::count(),
                'active_reservations' => WorkerReservation::where('state', 'Reserved')
                    ->where('expires_at', '>', now())
                    ->count(),
                'expired_today' => WorkerReservation::where('state', 'Expired')
                    ->whereDate('expires_at', today())
                    ->count(),
                'confirmed_today' => WorkerReservation::where('state', 'Confirmed')
                    ->whereDate('updated_at', today())
                    ->count(),
                'reservations_by_state' => WorkerReservation::selectRaw('state, COUNT(*) as count')
                    ->groupBy('state')
                    ->pluck('count', 'state'),
                'expiring_soon' => WorkerReservation::where('state', 'Reserved')
                    ->whereBetween('expires_at', [now(), now()->addHours(1)])
                    ->count()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch reservation statistics', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics'
            ], 500);
        }
    }
}