<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('nationality_id');
            $table->uuid('profession_id');
            $table->uuid('contract_duration_id');
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('SAR'); // SAR, USD
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->uuid('created_by')->nullable();
            $table->uuid('modified_by')->nullable();
            $table->timestamps();
            
            $table->foreign('nationality_id')->references('id')->on('nationalities');
            $table->foreign('profession_id')->references('id')->on('professions');
            $table->foreign('contract_duration_id')->references('id')->on('contract_durations');
            $table->unique(['nationality_id', 'profession_id', 'contract_duration_id']);
            $table->index(['is_active', 'sort_order']);
            $table->index(['price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};