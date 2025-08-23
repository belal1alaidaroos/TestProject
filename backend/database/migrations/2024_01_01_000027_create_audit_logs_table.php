<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_name'); // Contract, Worker, Address, Discount, Package, Notification, Proposal, Problem
            $table->uuid('entity_id');
            $table->enum('action_type', ['create', 'update', 'delete', 'status_change']);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->uuid('user_id');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['entity_name', 'entity_id']);
            $table->index(['user_id']);
            $table->index(['action_type']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};