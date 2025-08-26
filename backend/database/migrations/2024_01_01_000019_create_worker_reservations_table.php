<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('worker_id');
            $table->uuid('customer_id');
            $table->uuid('contract_id')->nullable();
            $table->enum('state', ['AwaitingContract', 'AwaitingPayment', 'Completed', 'Cancelled'])->default('AwaitingContract');
            $table->timestamp('expires_at');
            $table->uuid('created_by')->nullable();
            $table->uuid('modified_by')->nullable();
            $table->timestamps();
            
            $table->foreign('worker_id')->references('id')->on('workers');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('contract_id')->references('id')->on('contracts');
            $table->index(['worker_id', 'state']);
            $table->index(['expires_at']);
            $table->index(['customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_reservations');
    }
};