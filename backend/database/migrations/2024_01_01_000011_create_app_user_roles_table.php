<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_user_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('app_user_id');
            $table->uuid('app_role_id');
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            
            $table->foreign('app_user_id')->references('id')->on('app_users')->onDelete('cascade');
            $table->foreign('app_role_id')->references('id')->on('app_roles')->onDelete('cascade');
            $table->unique(['app_user_id', 'app_role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_user_roles');
    }
};