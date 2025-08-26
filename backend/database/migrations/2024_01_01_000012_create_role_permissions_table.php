<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('app_role_id');
            $table->uuid('app_resource_id');
            $table->enum('action', ['Create', 'Read', 'Update', 'Delete', 'Import', 'Export']);
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            
            $table->foreign('app_role_id')->references('id')->on('app_roles')->onDelete('cascade');
            $table->foreign('app_resource_id')->references('id')->on('app_resources')->onDelete('cascade');
            $table->unique(['app_role_id', 'app_resource_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};