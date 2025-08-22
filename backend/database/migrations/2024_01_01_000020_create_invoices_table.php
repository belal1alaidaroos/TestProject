<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('contract_id');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->enum('status', ['Unpaid', 'Paid', 'Overdue'])->default('Unpaid');
            $table->string('erp_ref')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('modified_by')->nullable();
            $table->timestamps();
            
            $table->foreign('contract_id')->references('id')->on('contracts');
            $table->index(['status', 'due_date']);
            $table->index(['contract_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};