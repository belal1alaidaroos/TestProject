<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->decimal('amount', 10, 2);
            $table->timestamp('paid_at');
            $table->enum('method', ['bank_transfer', 'credit_card', 'cash', 'check']);
            $table->string('erp_ref')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('modified_by')->nullable();
            $table->timestamps();
            
            $table->foreign('invoice_id')->references('id')->on('invoices');
            $table->index(['invoice_id']);
            $table->index(['paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};