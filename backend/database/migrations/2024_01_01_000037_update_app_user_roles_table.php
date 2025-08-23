<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_user_roles', function (Blueprint $table) {
            // Add new fields for enhanced role management
            $table->uuid('assigned_by')->nullable()->after('app_role_id');
            $table->timestamp('assigned_at')->nullable()->after('assigned_by');
            $table->boolean('is_primary')->default(false)->after('assigned_at');
            $table->text('notes')->nullable()->after('is_primary');
            
            // Add foreign key constraints
            $table->foreign('assigned_by')->references('id')->on('app_users');
            
            // Add indexes
            $table->index(['app_user_id', 'is_primary']);
            $table->index(['assigned_by']);
        });
    }

    public function down(): void
    {
        Schema::table('app_user_roles', function (Blueprint $table) {
            $table->dropForeign(['assigned_by']);
            
            $table->dropIndex(['app_user_id', 'is_primary']);
            $table->dropIndex(['assigned_by']);
            
            $table->dropColumn([
                'assigned_by',
                'assigned_at',
                'is_primary',
                'notes'
            ]);
        });
    }
};