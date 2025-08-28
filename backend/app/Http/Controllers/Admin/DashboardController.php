<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Worker;
use App\Models\Request as WorkerRequest;
use App\Models\Proposal;
use App\Models\Contract;
use App\Models\Reservation;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'month'); // day, week, month, year
            $startDate = $this->getStartDate($period);
            $endDate = now();

            // User statistics
            $userStats = $this->getUserStatistics($startDate, $endDate);
            
            // Worker statistics
            $workerStats = $this->getWorkerStatistics($startDate, $endDate);
            
            // Request statistics
            $requestStats = $this->getRequestStatistics($startDate, $endDate);
            
            // Proposal statistics
            $proposalStats = $this->getProposalStatistics($startDate, $endDate);
            
            // Contract statistics
            $contractStats = $this->getContractStatistics($startDate, $endDate);
            
            // Payment statistics
            $paymentStats = $this->getPaymentStatistics($startDate, $endDate);
            
            // Revenue statistics
            $revenueStats = $this->getRevenueStatistics($startDate, $endDate);

            // Recent activities
            $recentActivities = $this->getRecentActivities();

            return response()->json([
                'status' => 200,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'period' => $period,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'user_statistics' => $userStats,
                    'worker_statistics' => $workerStats,
                    'request_statistics' => $requestStats,
                    'proposal_statistics' => $proposalStats,
                    'contract_statistics' => $contractStats,
                    'payment_statistics' => $paymentStats,
                    'revenue_statistics' => $revenueStats,
                    'recent_activities' => $recentActivities
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get start date based on period
     */
    private function getStartDate(string $period): Carbon
    {
        return match($period) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth()
        };
    }

    /**
     * Get user statistics
     */
    private function getUserStatistics(Carbon $startDate, Carbon $endDate): array
    {
        $totalUsers = User::count();
        $newUsers = User::whereBetween('created_at', [$startDate, $endDate])->count();
        $activeUsers = User::where('is_active', true)->count();
        $suspendedUsers = User::where('status', 'suspended')->count();

        $usersByType = User::select('user_type', DB::raw('count(*) as count'))
            ->groupBy('user_type')
            ->get()
            ->pluck('count', 'user_type')
            ->toArray();

        return [
            'total' => $totalUsers,
            'new' => $newUsers,
            'active' => $activeUsers,
            'suspended' => $suspendedUsers,
            'by_type' => $usersByType
        ];
    }

    /**
     * Get worker statistics
     */
    private function getWorkerStatistics(Carbon $startDate, Carbon $endDate): array
    {
        $totalWorkers = Worker::count();
        $newWorkers = Worker::whereBetween('created_at', [$startDate, $endDate])->count();
        $availableWorkers = Worker::where('status', 'available')->count();
        $assignedWorkers = Worker::where('status', 'assigned')->count();
        $reservedWorkers = Worker::where('status', 'reserved')->count();

        $workersByStatus = Worker::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => $totalWorkers,
            'new' => $newWorkers,
            'available' => $availableWorkers,
            'assigned' => $assignedWorkers,
            'reserved' => $reservedWorkers,
            'by_status' => $workersByStatus
        ];
    }

    /**
     * Get request statistics
     */
    private function getRequestStatistics(Carbon $startDate, Carbon $endDate): array
    {
        $totalRequests = WorkerRequest::count();
        $newRequests = WorkerRequest::whereBetween('created_at', [$startDate, $endDate])->count();
        $openRequests = WorkerRequest::where('status', 'open')->count();
        $closedRequests = WorkerRequest::where('status', 'closed')->count();
        $cancelledRequests = WorkerRequest::where('status', 'cancelled')->count();

        $requestsByStatus = WorkerRequest::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => $totalRequests,
            'new' => $newRequests,
            'open' => $openRequests,
            'closed' => $closedRequests,
            'cancelled' => $cancelledRequests,
            'by_status' => $requestsByStatus
        ];
    }

    /**
     * Get proposal statistics
     */
    private function getProposalStatistics(Carbon $startDate, Carbon $endDate): array
    {
        $totalProposals = Proposal::count();
        $newProposals = Proposal::whereBetween('created_at', [$startDate, $endDate])->count();
        $pendingProposals = Proposal::where('status', 'pending')->count();
        $approvedProposals = Proposal::where('status', 'approved')->count();
        $rejectedProposals = Proposal::where('status', 'rejected')->count();

        $proposalsByStatus = Proposal::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => $totalProposals,
            'new' => $newProposals,
            'pending' => $pendingProposals,
            'approved' => $approvedProposals,
            'rejected' => $rejectedProposals,
            'by_status' => $proposalsByStatus
        ];
    }

    /**
     * Get contract statistics
     */
    private function getContractStatistics(Carbon $startDate, Carbon $endDate): array
    {
        $totalContracts = Contract::count();
        $newContracts = Contract::whereBetween('created_at', [$startDate, $endDate])->count();
        $activeContracts = Contract::where('status', 'active')->count();
        $completedContracts = Contract::where('status', 'completed')->count();
        $cancelledContracts = Contract::where('status', 'cancelled')->count();

        $contractsByStatus = Contract::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => $totalContracts,
            'new' => $newContracts,
            'active' => $activeContracts,
            'completed' => $completedContracts,
            'cancelled' => $cancelledContracts,
            'by_status' => $contractsByStatus
        ];
    }

    /**
     * Get payment statistics
     */
    private function getPaymentStatistics(Carbon $startDate, Carbon $endDate): array
    {
        $totalPayments = Payment::count();
        $newPayments = Payment::whereBetween('created_at', [$startDate, $endDate])->count();
        $pendingPayments = Payment::where('status', 'pending')->count();
        $completedPayments = Payment::where('status', 'completed')->count();
        $failedPayments = Payment::where('status', 'failed')->count();

        $paymentsByStatus = Payment::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => $totalPayments,
            'new' => $newPayments,
            'pending' => $pendingPayments,
            'completed' => $completedPayments,
            'failed' => $failedPayments,
            'by_status' => $paymentsByStatus
        ];
    }

    /**
     * Get revenue statistics
     */
    private function getRevenueStatistics(Carbon $startDate, Carbon $endDate): array
    {
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $periodRevenue = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $revenueByMethod = Payment::where('status', 'completed')
            ->select('payment_method', DB::raw('sum(amount) as total'))
            ->groupBy('payment_method')
            ->get()
            ->pluck('total', 'payment_method')
            ->toArray();

        $dailyRevenue = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('sum(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total' => $totalRevenue,
            'period' => $periodRevenue,
            'by_method' => $revenueByMethod,
            'daily' => $dailyRevenue
        ];
    }

    /**
     * Get recent activities
     */
    private function getRecentActivities(): array
    {
        $activities = [];

        // Recent users
        $recentUsers = User::with('roles')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($user) {
                return [
                    'type' => 'user_created',
                    'title' => 'New user registered',
                    'description' => $user->name . ' (' . $user->user_type . ')',
                    'timestamp' => $user->created_at,
                    'data' => $user
                ];
            });

        // Recent workers
        $recentWorkers = Worker::with('profession')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($worker) {
                return [
                    'type' => 'worker_created',
                    'title' => 'New worker added',
                    'description' => $worker->full_name . ' (' . $worker->profession->name . ')',
                    'timestamp' => $worker->created_at,
                    'data' => $worker
                ];
            });

        // Recent requests
        $recentRequests = WorkerRequest::with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($request) {
                return [
                    'type' => 'request_created',
                    'title' => 'New request submitted',
                    'description' => 'Request by ' . $request->customer->name,
                    'timestamp' => $request->created_at,
                    'data' => $request
                ];
            });

        // Recent proposals
        $recentProposals = Proposal::with(['agency', 'request'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($proposal) {
                return [
                    'type' => 'proposal_submitted',
                    'title' => 'New proposal submitted',
                    'description' => 'Proposal by ' . $proposal->agency->name,
                    'timestamp' => $proposal->created_at,
                    'data' => $proposal
                ];
            });

        // Recent contracts
        $recentContracts = Contract::with(['customer', 'worker'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($contract) {
                return [
                    'type' => 'contract_created',
                    'title' => 'New contract created',
                    'description' => 'Contract between ' . $contract->customer->name . ' and ' . $contract->worker->full_name,
                    'timestamp' => $contract->created_at,
                    'data' => $contract
                ];
            });

        // Merge and sort all activities
        $activities = collect([$recentUsers, $recentWorkers, $recentRequests, $recentProposals, $recentContracts])
            ->flatten(1)
            ->sortByDesc('timestamp')
            ->take(20)
            ->values()
            ->toArray();

        return $activities;
    }
}