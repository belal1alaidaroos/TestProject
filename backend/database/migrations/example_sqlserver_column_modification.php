<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\SqlServerMigrationHelper;

return new class extends Migration
{
    use SqlServerMigrationHelper;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the 'status' column in 'workers' table
        $this->safeColumnModification('workers', 'status', function () {
            Schema::table('workers', function (Blueprint $table) {
                $table->string('status', 50)->nullable()->change();
            });
        });

        // Modify the 'nationality_id' column in 'workers' table
        $this->safeColumnModification('workers', 'nationality_id', function () {
            Schema::table('workers', function (Blueprint $table) {
                $table->uuid('nationality_id')->nullable()->change(); // Changed to uuid
            });
        });

        // Modify the 'profession_id' column in 'workers' table
        $this->safeColumnModification('workers', 'profession_id', function () {
            Schema::table('workers', function (Blueprint $table) {
                $table->uuid('profession_id')->nullable()->change(); // Changed to uuid
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the 'status' column modification
        $this->safeColumnModification('workers', 'status', function () {
            Schema::table('workers', function (Blueprint $table) {
                $table->string('status', 20)->nullable()->change(); // Revert to original size
            });
        });

        // Revert the 'nationality_id' column modification
        $this->safeColumnModification('workers', 'nationality_id', function () {
            Schema::table('workers', function (Blueprint $table) {
                $table->uuid('nationality_id')->nullable()->change(); // Keep as uuid for rollback consistency
            });
        });

        // Revert the 'profession_id' column modification
        $this->safeColumnModification('workers', 'profession_id', function () {
            Schema::table('workers', function (Blueprint $table) {
                $table->uuid('profession_id')->nullable()->change(); // Keep as uuid for rollback consistency
            });
        });
    }
};
