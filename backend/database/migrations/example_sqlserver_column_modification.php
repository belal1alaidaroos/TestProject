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
        // Example: Modify the 'status' column in 'workers' table
        $this->safeColumnModification('workers', 'status', function () {
            Schema::table('workers', function (Blueprint $table) {
                $table->string('status', 50)->nullable()->change();
            });
        });

        // Example: Modify the 'nationality_id' column in 'workers' table
        $this->safeColumnModification('workers', 'nationality_id', function () {
            Schema::table('workers', function (Blueprint $table) {
                $table->unsignedBigInteger('nationality_id')->nullable()->change();
            });
        });

        // Example: Modify the 'profession_id' column in 'workers' table
        $this->safeColumnModification('workers', 'profession_id', function () {
            Schema::table('workers', function (Blueprint $table) {
                $table->unsignedBigInteger('profession_id')->nullable()->change();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Example: Revert the 'status' column modification
        $this->safeColumnModification('workers', 'status', function () {
            Schema::table('workers', function (Blueprint $table) {
                $table->string('status', 20)->nullable()->change(); // Revert to original size
            });
        });

        // Example: Revert the 'nationality_id' column modification
        $this->safeColumnModification('workers', 'nationality_id', function () {
            Schema::table('workers', function (Blueprint $table) {
                $table->unsignedInteger('nationality_id')->nullable()->change(); // Revert to original type
            });
        });

        // Example: Revert the 'profession_id' column modification
        $this->safeColumnModification('workers', 'profession_id', function () {
            Schema::table('workers', function (Blueprint $table) {
                $table->unsignedInteger('profession_id')->nullable()->change(); // Revert to original type
            });
        });
    }
};