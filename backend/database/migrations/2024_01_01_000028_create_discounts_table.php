<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('percentage', 5, 2); // Up to 999.99%
            $table->json('conditions')->nullable(); // JSON conditions for when discount applies
            $table->timestamp('valid_from');
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('max_uses')->nullable(); // NULL = unlimited
            $table->integer('used_count')->default(0);
            $table->uuid('created_by')->nullable();
            $table->uuid('modified_by')->nullable();
            $table->timestamps();
            
            $table->index(['is_active', 'valid_from', 'valid_until']);
            $table->index(['percentage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};