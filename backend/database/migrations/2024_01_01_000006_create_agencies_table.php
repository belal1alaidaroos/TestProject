<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('license_number')->unique();
            $table->string('contact_person');
            $table->string('phone');
            $table->string('email');
            $table->text('address');
            $table->uuid('country_id');
            $table->uuid('city_id');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->uuid('created_by')->nullable();
            $table->uuid('modified_by')->nullable();
            $table->timestamps();
            
            $table->foreign('country_id')->references('id')->on('countries');
            $table->foreign('city_id')->references('id')->on('cities');
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};