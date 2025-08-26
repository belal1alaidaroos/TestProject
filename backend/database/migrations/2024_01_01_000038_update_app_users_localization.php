<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_users', function (Blueprint $table) {
            // Add localization fields
            $table->string('preferred_language', 5)->default('en')->after('password');
            $table->string('timezone', 50)->default('UTC')->after('preferred_language');
            $table->string('date_format', 20)->default('Y-m-d')->after('timezone');
            $table->string('currency_preference', 3)->default('SAR')->after('date_format');
            
            // Add indexes
            $table->index(['preferred_language']);
            $table->index(['timezone']);
            $table->index(['currency_preference']);
        });
    }

    public function down(): void
    {
        Schema::table('app_users', function (Blueprint $table) {
            $table->dropIndex(['preferred_language']);
            $table->dropIndex(['timezone']);
            $table->dropIndex(['currency_preference']);
            
            $table->dropColumn([
                'preferred_language',
                'timezone',
                'date_format',
                'currency_preference'
            ]);
        });
    }
};