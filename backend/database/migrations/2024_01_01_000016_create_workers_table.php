<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_en');
            $table->string('name_ar');
            $table->date('date_of_birth');
            $table->uuid('nationality_id');
            $table->uuid('profession_id');
            $table->uuid('agency_id');
            $table->uuid('recruitment_request_id')->nullable();
            $table->enum('status', ['Ready', 'ReservedAwaitingContract', 'ReservedAwaitingPayment', 'AssignedToContract'])->default('Ready');
            $table->uuid('current_contract_id')->nullable();
            $table->uuid('photo_file_id')->nullable();
            $table->text('experience_summary')->nullable();
            $table->integer('experience_years')->default(0);
            $table->uuid('created_by')->nullable();
            $table->uuid('modified_by')->nullable();
            $table->timestamps();
            
            $table->foreign('nationality_id')->references('id')->on('nationalities');
            $table->foreign('profession_id')->references('id')->on('professions');
            $table->foreign('agency_id')->references('id')->on('agencies');
            $table->foreign('recruitment_request_id')->references('id')->on('recruitment_requests');
            //$table->foreign('current_contract_id')->references('id')->on('contracts');
            $table->index(['status', 'nationality_id', 'profession_id']);
            $table->index(['agency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};