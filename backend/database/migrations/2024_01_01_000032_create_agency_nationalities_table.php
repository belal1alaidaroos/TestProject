<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_nationalities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agency_id');
            $table->uuid('nationality_id');
            $table->string('label')->nullable(); // Custom agency label e.g. "Country"
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->uuid('modified_by')->nullable();
            $table->timestamps();
            
            $table->foreign('agency_id')->references('id')->on('agencies')->onDelete('cascade');
            $table->foreign('nationality_id')->references('id')->on('nationalities');
            $table->unique(['agency_id', 'nationality_id']);
            $table->index(['agency_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_nationalities');
    }
};