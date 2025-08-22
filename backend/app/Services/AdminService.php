<?php

namespace App\Services;

use App\Models\AppUser;
use App\Models\Contract;
use App\Models\SupplierProposal;
use App\Models\SystemSetting;
use App\Models\Worker;
use App\Models\WorkerReservation;
use App\Models\RecruitmentRequest;
use App\Models\AppRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminService
{
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        $stats = [
            'total_workers' => Worker::count(),
            'available_workers' => Worker::where('status', 'Ready')->count(),
            'reserved_workers' => Worker::where('status', 'Reserved')->count(),
            'total_customers' => AppUser::where('user_type', 'Customer')->count(),
            'total_agencies' => AppUser::where('user_type', 'Agency')->count(),
            'total_contracts' => Contract::count(),
            'active_contracts' => Contract::where('status', 'Active')->count(),
            'total_revenue' => Contract::where('status', 'Active')->sum('total_amount'),
            'pending_proposals' => SupplierProposal::where('status', 'Submitted')->count(),
            'approved_proposals' => SupplierProposal::where('status', 'Approved')->count(),
            'rejected_proposals' => SupplierProposal::where('status', 'Rejected')->count(),
            'recent_activities' => $this->getRecentActivities(),
            'monthly_revenue' => $this->getMonthlyRevenue(),
            'worker_status_distribution' => $this->getWorkerStatusDistribution(),
            'contract_status_distribution' => $this->getContractStatusDistribution(),
        ];

        return $stats;
    }

    /**
     * Get recent activities
     */
    private function getRecentActivities(): array
    {
        $activities = [];

        // Get recent contracts
        $recentContracts = Contract::with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($recentContracts as $contract) {
            $activities[] = [
                'id' => $contract->id,
                'type' => 'contract_created',
                'description' => "New contract created by {$contract->customer->name_en}",
                'created_at' => $contract->created_at->toISOString(),
                'user_name' => $contract->customer->name_en,
            ];
        }

        // Get recent proposals
        $recentProposals = SupplierProposal::with('agency')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($recentProposals as $proposal) {
            $activities[] = [
                'id' => $proposal->id,
                'type' => 'proposal_submitted',
                'description' => "New proposal submitted by {$proposal->agency->name_en}",
                'created_at' => $proposal->created_at->toISOString(),
                'user_name' => $proposal->agency->name_en,
            ];
        }

        // Sort by created_at and return top 10
        usort($activities, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return array_slice($activities, 0, 10);
    }

    /**
     * Get monthly revenue for the last 12 months
     */
    private function getMonthlyRevenue(): array
    {
        $revenue = [];
        $months = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            $monthRevenue = Contract::where('status', 'Active')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('total_amount');

            $revenue[] = [
                'month' => $date->format('M Y'),
                'revenue' => $monthRevenue,
            ];
        }

        return $revenue;
    }

    /**
     * Get worker status distribution
     */
    private function getWorkerStatusDistribution(): array
    {
        $distribution = Worker::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->toArray();

        return $distribution;
    }

    /**
     * Get contract status distribution
     */
    private function getContractStatusDistribution(): array
    {
        $distribution = Contract::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->toArray();

        return $distribution;
    }

    /**
     * Get proposals for review
     */
    public function getProposalsForReview(array $filters = []): array
    {
        $query = SupplierProposal::with(['agency', 'request.profession']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['request_id'])) {
            $query->where('request_id', 'like', '%' . $filters['request_id'] . '%');
        }

        if (isset($filters['agency_id'])) {
            $query->where('agency_id', $filters['agency_id']);
        }

        $perPage = $filters['per_page'] ?? 10;
        $proposals = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return [
            'data' => $proposals->items(),
            'current_page' => $proposals->currentPage(),
            'last_page' => $proposals->lastPage(),
            'per_page' => $proposals->perPage(),
            'total' => $proposals->total(),
        ];
    }

    /**
     * Approve a proposal
     */
    public function approveProposal(string $proposalId, string $adminId, array $data): void
    {
        DB::transaction(function () use ($proposalId, $adminId, $data) {
            $proposal = SupplierProposal::findOrFail($proposalId);
            
            if ($proposal->status !== 'Submitted') {
                throw new \Exception('Proposal is not in submitted status');
            }

            $proposal->update([
                'status' => 'Approved',
                'approved_by' => $adminId,
                'approved_at' => now(),
                'admin_notes' => $data['notes'] ?? null,
            ]);

            // Update recruitment request awarded quantity
            $request = $proposal->request;
            $request->increment('quantity_awarded', $proposal->offered_qty);

            // Check if request is fully awarded
            if ($request->quantity_awarded >= $request->quantity_required) {
                $request->update(['status' => 'FullyAwarded']);
            } else {
                $request->update(['status' => 'PartiallyAwarded']);
            }
        });
    }

    /**
     * Reject a proposal
     */
    public function rejectProposal(string $proposalId, string $adminId, array $data): void
    {
        $proposal = SupplierProposal::findOrFail($proposalId);
        
        if ($proposal->status !== 'Submitted') {
            throw new \Exception('Proposal is not in submitted status');
        }

        $proposal->update([
            'status' => 'Rejected',
            'approved_by' => $adminId,
            'approved_at' => now(),
            'admin_notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Partially approve a proposal
     */
    public function partialApproveProposal(string $proposalId, string $adminId, array $data): void
    {
        DB::transaction(function () use ($proposalId, $adminId, $data) {
            $proposal = SupplierProposal::findOrFail($proposalId);
            
            if ($proposal->status !== 'Submitted') {
                throw new \Exception('Proposal is not in submitted status');
            }

            if ($data['approved_qty'] > $proposal->offered_qty) {
                throw new \Exception('Approved quantity cannot exceed offered quantity');
            }

            $proposal->update([
                'status' => 'PartiallyApproved',
                'approved_qty' => $data['approved_qty'],
                'approved_by' => $adminId,
                'approved_at' => now(),
                'admin_notes' => $data['notes'] ?? null,
            ]);

            // Update recruitment request awarded quantity
            $request = $proposal->request;
            $request->increment('quantity_awarded', $data['approved_qty']);

            // Check if request is fully awarded
            if ($request->quantity_awarded >= $request->quantity_required) {
                $request->update(['status' => 'FullyAwarded']);
            } else {
                $request->update(['status' => 'PartiallyAwarded']);
            }
        });
    }

    /**
     * Get users with filtering
     */
    public function getUsers(array $filters = []): array
    {
        $query = AppUser::with('roles');

        // Apply filters
        if (isset($filters['user_type'])) {
            $query->where('user_type', $filters['user_type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $perPage = $filters['per_page'] ?? 10;
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return [
            'data' => $users->items(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
        ];
    }

    /**
     * Get a specific user
     */
    public function getUser(string $userId): ?AppUser
    {
        return AppUser::with('roles')->find($userId);
    }

    /**
     * Update a user
     */
    public function updateUser(string $userId, array $data): AppUser
    {
        $user = AppUser::findOrFail($userId);
        
        $user->update($data);

        return $user->load('roles');
    }

    /**
     * Update user status
     */
    public function updateUserStatus(string $userId, string $status): void
    {
        $user = AppUser::findOrFail($userId);
        $user->update(['status' => $status]);
    }

    /**
     * Update user roles
     */
    public function updateUserRoles(string $userId, array $roleIds): void
    {
        $user = AppUser::findOrFail($userId);
        $user->roles()->sync($roleIds);
    }

    /**
     * Get system settings
     */
    public function getSystemSettings(): array
    {
        return SystemSetting::all()->toArray();
    }

    /**
     * Update system settings
     */
    public function updateSystemSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            SystemSetting::where('key', $key)->update(['value' => $value]);
        }
    }

    /**
     * Reset system settings to defaults
     */
    public function resetSystemSettings(): void
    {
        // Reset to default values
        $defaults = [
            'app_name' => 'Worker Management System',
            'app_description' => 'Comprehensive worker management and recruitment platform',
            'company_name' => 'Your Company Name',
            'company_email' => 'info@company.com',
            'company_phone' => '+966-XX-XXXXXXX',
            'reservation_ttl_hours' => '24',
            'payment_ttl_hours' => '48',
            'max_file_size_mb' => '5',
            'allowed_file_types' => 'pdf,doc,docx,jpg,jpeg,png',
            'smtp_host' => 'smtp.mailtrap.io',
            'smtp_port' => '2525',
            'smtp_username' => '',
            'smtp_password' => '',
            'sms_provider' => 'internal',
            'erp_integration' => 'disabled',
            'maintenance_mode' => 'false',
        ];

        foreach ($defaults as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }

    /**
     * Get agencies for filtering
     */
    public function getAgencies(): array
    {
        return AppUser::where('user_type', 'Agency')
            ->where('status', 'Active')
            ->select('id', 'name_en', 'name_ar')
            ->get()
            ->toArray();
    }

    /**
     * Get roles for assignment
     */
    public function getRoles(): array
    {
        return AppRole::select('id', 'name_en', 'name_ar')
            ->get()
            ->toArray();
    }
}