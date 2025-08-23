<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            // Add new fields for enhanced worker management
            $table->string('worker_number')->unique()->after('id');
            $table->string('iqama_number')->nullable()->after('profession_id');
            $table->date('iqama_expiry_date')->nullable()->after('iqama_number');
            $table->string('bank_account_number')->nullable()->after('iqama_expiry_date');
            $table->string('sim_card_number')->nullable()->after('bank_account_number');
            $table->enum('lifecycle_status', ['Medical Check', 'Iqama Issued', 'Bank Account', 'SIM Card Issued', 'Ready to Work'])->default('Medical Check')->after('sim_card_number');
            $table->date('arrival_date')->nullable()->after('lifecycle_status');
            $table->text('notes')->nullable()->after('arrival_date');
            
            // Update status enum to include new statuses
            $table->enum('status', ['Ready', 'ReservedAwaitingContract', 'ReservedAwaitingPayment', 'AssignedToContract', 'Medical Check', 'Iqama Issued', 'Bank Account', 'SIM Card Issued', 'Ready to Work', 'On Leave', 'Terminated'])->default('Ready')->change();
            
            // Add indexes
            $table->index(['worker_number']);
            $table->index(['iqama_number']);
            $table->index(['lifecycle_status']);
            $table->index(['arrival_date']);
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropIndex(['worker_number']);
            $table->dropIndex(['iqama_number']);
            $table->dropIndex(['lifecycle_status']);
            $table->dropIndex(['arrival_date']);
            
            $table->dropColumn([
                'worker_number',
                'iqama_number',
                'iqama_expiry_date',
                'bank_account_number',
                'sim_card_number',
                'lifecycle_status',
                'arrival_date',
                'notes'
            ]);
            
            // Revert status enum
            $table->enum('status', ['Ready', 'ReservedAwaitingContract', 'ReservedAwaitingPayment', 'AssignedToContract'])->default('Ready')->change();
        });
    }
};