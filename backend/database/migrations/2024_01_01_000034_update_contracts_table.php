<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Add new fields for enhanced contract management (only non-existing ones)
            $table->string('contract_number')->unique()->after('id');
            $table->uuid('assigned_employee_id')->nullable()->after('worker_id');
            $table->uuid('delivery_address_id')->nullable()->after('package_id');
            $table->decimal('original_amount', 10, 2)->after('total_amount');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('original_amount');
            $table->uuid('applied_discount_id')->nullable()->after('discount_amount');
            $table->string('currency')->default('SAR')->after('applied_discount_id');
            $table->enum('payment_status', ['Pending', 'Partial', 'Paid', 'Failed'])->default('Pending')->after('status');
            $table->text('notes')->nullable()->after('payment_status');
            $table->text('employee_notes')->nullable()->after('notes');
            $table->timestamp('status_updated_at')->nullable()->after('employee_notes');
            $table->uuid('status_updated_by')->nullable()->after('status_updated_at');
            
            // Add foreign key constraints
            $table->foreign('assigned_employee_id')->references('id')->on('app_users');
            $table->foreign('delivery_address_id')->references('id')->on('customer_addresses');
            $table->foreign('applied_discount_id')->references('id')->on('discounts');
            $table->foreign('status_updated_by')->references('id')->on('app_users');
            
            // Add indexes
            $table->index(['contract_number']);
            $table->index(['assigned_employee_id']);
            $table->index(['delivery_address_id']);
            $table->index(['applied_discount_id']);
            $table->index(['currency']);
            $table->index(['payment_status']);
            $table->index(['status_updated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['assigned_employee_id']);
            $table->dropForeign(['delivery_address_id']);
            $table->dropForeign(['applied_discount_id']);
            $table->dropForeign(['status_updated_by']);
            
            $table->dropIndex(['contract_number']);
            $table->dropIndex(['assigned_employee_id']);
            $table->dropIndex(['delivery_address_id']);
            $table->dropIndex(['applied_discount_id']);
            $table->dropIndex(['currency']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['status_updated_at']);
            
            $table->dropColumn([
                'contract_number',
                'assigned_employee_id',
                'delivery_address_id',
                'original_amount',
                'discount_amount',
                'applied_discount_id',
                'currency',
                'payment_status',
                'notes',
                'employee_notes',
                'status_updated_at',
                'status_updated_by'
            ]);
        });
    }
};