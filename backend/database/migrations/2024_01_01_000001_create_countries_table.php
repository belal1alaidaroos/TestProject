<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('code', 3)->unique();
            $table->string('phone_code', 5);
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->uuid('modified_by')->nullable();
            $table->timestamps();
            
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};