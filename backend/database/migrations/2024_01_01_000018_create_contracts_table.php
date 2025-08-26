<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->uuid('worker_id');
            $table->uuid('package_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['Draft', 'AwaitingPayment', 'Active', 'Cancelled', 'Terminated'])->default('Draft');
            $table->timestamp('signed_at')->nullable();
            $table->uuid('reservation_id')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->text('terms_conditions')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('modified_by')->nullable();
            $table->timestamps();
            
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('worker_id')->references('id')->on('workers');
            $table->foreign('package_id')->references('id')->on('packages');
           // $table->foreign('reservation_id')->references('id')->on('worker_reservations');
            $table->index(['status', 'customer_id']);
            $table->index(['worker_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};