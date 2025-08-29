<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Notification::where('user_id', auth()->id());
            
            // Filter by read status
            if ($request->has('unread_only') && $request->unread_only === 'true') {
                $query->whereNull('read_at');
            }
            
            // Filter by type
            if ($request->has('type') && $request->type !== '') {
                $query->where('type', $request->type);
            }
            
            // Search in title or message
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('message', 'like', "%{$search}%");
                });
            }
            
            // Get unread count
            $unreadCount = Notification::where('user_id', auth()->id())
                ->whereNull('read_at')
                ->count();
            
            // Pagination
            $perPage = $request->get('per_page', 20);
            $notifications = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $notifications,
                'unread_count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch notifications', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications'
            ], 500);
        }
    }

    /**
     * Display the specified notification.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $notification = Notification::where('user_id', auth()->id())
                ->findOrFail($id);
            
            // Mark as read if not already
            if (!$notification->read_at) {
                $notification->update(['read_at' => now()]);
            }
            
            return response()->json([
                'success' => true,
                'data' => $notification
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to fetch notification', [
                'notification_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notification'
            ], 500);
        }
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(string $id): JsonResponse
    {
        try {
            $notification = Notification::where('user_id', auth()->id())
                ->whereNull('read_at')
                ->findOrFail($id);
            
            $notification->update(['read_at' => now()]);
            
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'data' => $notification
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found or already read'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read', [
                'notification_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read'
            ], 500);
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $updated = Notification::where('user_id', auth()->id())
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
            
            return response()->json([
                'success' => true,
                'message' => "Marked {$updated} notifications as read"
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notifications as read'
            ], 500);
        }
    }

    /**
     * Remove the specified notification.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $notification = Notification::where('user_id', auth()->id())
                ->findOrFail($id);
            
            $notification->delete();
            
            Log::info('Notification deleted', [
                'notification_id' => $id,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete notification', [
                'notification_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification'
            ], 500);
        }
    }

    /**
     * Get notification statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            $userId = auth()->id();
            
            $stats = [
                'total_notifications' => Notification::where('user_id', $userId)->count(),
                'unread_notifications' => Notification::where('user_id', $userId)
                    ->whereNull('read_at')
                    ->count(),
                'notifications_by_type' => Notification::where('user_id', $userId)
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type'),
                'recent_notifications' => Notification::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch notification statistics', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics'
            ], 500);
        }
    }
}