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
            $table->uuid('delivery_address_id')->nullable()->after('package_id');
            $table->decimal('original_amount', 10, 2)->after('total_amount');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('original_amount');
            $table->uuid('applied_discount_id')->nullable()->after('discount_amount');
            
            // Add foreign key constraints
            $table->foreign('delivery_address_id')->references('id')->on('customer_addresses');
            $table->foreign('applied_discount_id')->references('id')->on('discounts');
            
            // Add indexes
            $table->index(['delivery_address_id']);
            $table->index(['applied_discount_id']);
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['delivery_address_id']);
            $table->dropForeign(['applied_discount_id']);
            
            $table->dropIndex(['delivery_address_id']);
            $table->dropIndex(['applied_discount_id']);
            
            $table->dropColumn([
                'delivery_address_id',
                'original_amount',
                'discount_amount',
                'applied_discount_id'
            ]);
        });
    }
};