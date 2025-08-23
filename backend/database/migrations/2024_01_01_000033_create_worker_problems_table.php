<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_problems', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('worker_id');
            $table->enum('problem_type', ['escape', 'refusal', 'non_compliance', 'misconduct', 'early_return']);
            $table->text('description');
            $table->date('date_reported');
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Closed'])->default('Pending');
            $table->enum('resolution_action', ['Dismissal', 'Re-training', 'Escalation'])->nullable();
            $table->text('resolution_notes')->nullable();
            $table->uuid('created_by');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->foreign('worker_id')->references('id')->on('workers')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('app_users');
            $table->foreign('approved_by')->references('id')->on('app_users');
            $table->index(['worker_id', 'status']);
            $table->index(['problem_type', 'status']);
            $table->index(['date_reported']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_problems');
    }
};