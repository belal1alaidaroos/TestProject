<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('contract_id');
            $table->string('phone');
            $table->string('session_token', 64)->unique();
            $table->timestamp('expires_at');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->enum('payment_method', ['card', 'bank_transfer', 'paypass'])->default('paypass');
            $table->string('otp_hash')->nullable();
            $table->integer('otp_attempts')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'status']);
            $table->index(['phone', 'status']);
            $table->index(['session_token']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_sessions');
    }
};