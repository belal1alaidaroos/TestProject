<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add assigned_employee_id to workers table
        Schema::table('workers', function (Blueprint $table) {
            $table->uuid('assigned_employee_id')->nullable()->after('agency_id');
            $table->foreign('assigned_employee_id')->references('id')->on('app_users')->onDelete('set null');
            $table->index(['assigned_employee_id']);
        });

        // Add assigned_employee_id to contracts table
        Schema::table('contracts', function (Blueprint $table) {
            $table->uuid('assigned_employee_id')->nullable()->after('customer_id');
            $table->text('employee_notes')->nullable()->after('assigned_employee_id');
            $table->timestamp('status_updated_at')->nullable()->after('employee_notes');
            $table->uuid('status_updated_by')->nullable()->after('status_updated_at');
            $table->foreign('assigned_employee_id')->references('id')->on('app_users')->onDelete('set null');
            $table->foreign('status_updated_by')->references('id')->on('app_users')->onDelete('set null');
            $table->index(['assigned_employee_id']);
            $table->index(['status_updated_by']);
        });

        // Add assigned_employee_id to worker_reservations table
        Schema::table('worker_reservations', function (Blueprint $table) {
            $table->uuid('assigned_employee_id')->nullable()->after('customer_id');
            $table->text('employee_notes')->nullable()->after('assigned_employee_id');
            $table->timestamp('status_updated_at')->nullable()->after('employee_notes');
            $table->uuid('status_updated_by')->nullable()->after('status_updated_at');
            $table->foreign('assigned_employee_id')->references('id')->on('app_users')->onDelete('set null');
            $table->foreign('status_updated_by')->references('id')->on('app_users')->onDelete('set null');
            $table->index(['assigned_employee_id']);
            $table->index(['status_updated_by']);
        });
    }

    public function down(): void
    {
        // Remove assigned_employee_id from worker_reservations table
        Schema::table('worker_reservations', function (Blueprint $table) {
            $table->dropForeign(['assigned_employee_id']);
            $table->dropForeign(['status_updated_by']);
            $table->dropIndex(['assigned_employee_id']);
            $table->dropIndex(['status_updated_by']);
            $table->dropColumn(['assigned_employee_id', 'employee_notes', 'status_updated_at', 'status_updated_by']);
        });

        // Remove assigned_employee_id from contracts table
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['assigned_employee_id']);
            $table->dropForeign(['status_updated_by']);
            $table->dropIndex(['assigned_employee_id']);
            $table->dropIndex(['status_updated_by']);
            $table->dropColumn(['assigned_employee_id', 'employee_notes', 'status_updated_at', 'status_updated_by']);
        });

        // Remove assigned_employee_id from workers table
        Schema::table('workers', function (Blueprint $table) {
            $table->dropForeign(['assigned_employee_id']);
            $table->dropIndex(['assigned_employee_id']);
            $table->dropColumn('assigned_employee_id');
        });
    }
};