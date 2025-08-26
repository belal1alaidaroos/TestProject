<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('auth_otps', function (Blueprint $table) {
            $table->uuid('created_by')->nullable()->after('user_agent');
            $table->uuid('modified_by')->nullable()->after('created_by');
            $table->softDeletes();
            
            $table->index(['deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auth_otps', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();
            $table->dropColumn(['created_by', 'modified_by']);
        });
    }
};
