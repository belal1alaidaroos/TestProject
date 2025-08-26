<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_proposals', function (Blueprint $table) {
            // Add new fields for enhanced proposal management
            $table->text('comments')->nullable()->after('terms');
            $table->text('internal_remarks')->nullable()->after('comments');
            $table->enum('agency_action', ['Accept', 'Reject'])->nullable()->after('internal_remarks');
            $table->integer('accepted_count')->nullable()->after('agency_action');
            $table->json('candidate_details')->nullable()->after('accepted_count'); // Array of candidate details
            $table->enum('review_status', ['Pending', 'Approved', 'Rejected', 'Partially Approved'])->default('Pending')->after('candidate_details');
            $table->text('review_notes')->nullable()->after('review_status');
            $table->uuid('reviewed_by')->nullable()->after('review_notes');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            
            // Add foreign key constraints
            $table->foreign('reviewed_by')->references('id')->on('app_users');
            
            // Add indexes
            $table->index(['agency_action']);
            $table->index(['review_status']);
            $table->index(['reviewed_by']);
        });
    }

    public function down(): void
    {
        Schema::table('supplier_proposals', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            
            $table->dropIndex(['agency_action']);
            $table->dropIndex(['review_status']);
            $table->dropIndex(['reviewed_by']);
            
            $table->dropColumn([
                'comments',
                'internal_remarks',
                'agency_action',
                'accepted_count',
                'candidate_details',
                'review_status',
                'review_notes',
                'reviewed_by',
                'reviewed_at'
            ]);
        });
    }
};