<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('app_user_id');
            $table->string('company_name_en')->nullable();
            $table->string('company_name_ar')->nullable();
            $table->string('contact_person');
            $table->string('phone');
            $table->string('email');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->uuid('created_by')->nullable();
            $table->uuid('modified_by')->nullable();
            $table->timestamps();
            
            $table->foreign('app_user_id')->references('id')->on('app_users');
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};