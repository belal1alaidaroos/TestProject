<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_name'); // Worker, Contract, CustomerAddress, Proposal, Problem
            $table->uuid('entity_id');
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->bigInteger('file_size');
            $table->string('file_extension');
            $table->text('description')->nullable();
            $table->uuid('uploaded_by');
            $table->timestamps();
            
            $table->index(['entity_name', 'entity_id']);
            $table->index(['uploaded_by']);
            $table->index(['file_extension']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};