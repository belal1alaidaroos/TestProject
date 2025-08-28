<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing index if it exists (cross-database compatible)
        try {
            Schema::table('workers', function (Blueprint $table) {
                $table->dropIndex(['status', 'nationality_id', 'profession_id']);
            });
        } catch (Exception $e) {
            // Index doesn't exist, continue
        }

        Schema::table('workers', function (Blueprint $table) {
            // Add new fields
            $table->string('worker_number')->unique()->after('id');
            $table->string('iqama_number')->nullable()->after('profession_id');
            $table->date('iqama_expiry_date')->nullable()->after('iqama_number');
            $table->string('bank_account_number')->nullable()->after('iqama_expiry_date');
            $table->string('sim_card_number')->nullable()->after('bank_account_number');

            // Use string instead of enum for cross-database compatibility
            $table->string('lifecycle_status')->default('Medical Check')->after('sim_card_number');
            $table->date('arrival_date')->nullable()->after('lifecycle_status');
            $table->text('notes')->nullable()->after('arrival_date');

            // Add indexes
            $table->index(['worker_number']);
            $table->index(['iqama_number']);
            $table->index(['lifecycle_status']);
            $table->index(['arrival_date']);
        });

        // Recreate the index
        Schema::table('workers', function (Blueprint $table) {
            $table->index(['status', 'nationality_id', 'profession_id'], 'workers_status_nationality_id_profession_id_index');
        });
    }

    public function down(): void
    {
        // Drop the recreated index
        try {
            Schema::table('workers', function (Blueprint $table) {
                $table->dropIndex(['status', 'nationality_id', 'profession_id']);
            });
        } catch (Exception $e) {
            // Index doesn't exist, continue
        }

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
        });

        // Optionally recreate the original index (if rollback)
        Schema::table('workers', function (Blueprint $table) {
            $table->index(['status', 'nationality_id', 'profession_id'], 'workers_status_nationality_id_profession_id_index');
        });
    }
};
