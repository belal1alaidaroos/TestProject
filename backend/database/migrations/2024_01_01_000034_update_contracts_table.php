<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Add new fields for enhanced contract management
            $table->string('contract_number')->unique()->after('id');
            $table->uuid('package_id')->nullable()->after('worker_id');
            $table->uuid('delivery_address_id')->nullable()->after('package_id');
            $table->decimal('original_amount', 10, 2)->after('total_amount');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('original_amount');
            $table->uuid('applied_discount_id')->nullable()->after('discount_amount');
            $table->string('currency', 3)->default('SAR')->after('applied_discount_id');
            $table->enum('status', ['Draft', 'Pending', 'Confirmed', 'In Progress', 'Completed', 'Cancelled'])->default('Draft')->after('currency');
            
            // Add foreign key constraints
            $table->foreign('package_id')->references('id')->on('packages');
            $table->foreign('delivery_address_id')->references('id')->on('customer_addresses');
            $table->foreign('applied_discount_id')->references('id')->on('discounts');
            
            // Add indexes
            $table->index(['contract_number']);
            $table->index(['status']);
            $table->index(['package_id']);
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropForeign(['delivery_address_id']);
            $table->dropForeign(['applied_discount_id']);
            
            $table->dropIndex(['contract_number']);
            $table->dropIndex(['status']);
            $table->dropIndex(['package_id']);
            
            $table->dropColumn([
                'contract_number',
                'package_id',
                'delivery_address_id',
                'original_amount',
                'discount_amount',
                'applied_discount_id',
                'currency',
                'status'
            ]);
        });
    }
};