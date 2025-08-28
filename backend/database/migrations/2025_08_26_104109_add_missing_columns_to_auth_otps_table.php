<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auth_otps', function (Blueprint $table) {
            if (!Schema::hasColumn('auth_otps', 'created_by')) {
                $table->uuid('created_by')->nullable()->after('user_agent');
            }

            if (!Schema::hasColumn('auth_otps', 'modified_by')) {
                $table->uuid('modified_by')->nullable()->after('created_by');
            }

            if (!Schema::hasColumn('auth_otps', 'deleted_at')) {
                $table->softDeletes();
                $table->index(['deleted_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('auth_otps', function (Blueprint $table) {
            if (Schema::hasColumn('auth_otps', 'deleted_at')) {
                $table->dropIndex(['deleted_at']);
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('auth_otps', 'created_by')) {
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('auth_otps', 'modified_by')) {
                $table->dropColumn('modified_by');
            }
        });
    }
};
