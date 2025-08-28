<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_proposals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('request_id');
            $table->uuid('agency_id');
            $table->integer('offered_qty');
            $table->decimal('unit_price', 10, 2);
            $table->integer('lead_time_days')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->uuid('attachment_file_id')->nullable();
            $table->enum('status', ['Submitted', 'Reviewed', 'Approved', 'PartiallyApproved', 'Rejected', 'Cancelled'])->default('Submitted');
            $table->integer('approved_qty')->default(0);
            $table->text('rejection_reason')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('modified_by')->nullable();
            $table->timestamps();
            
            $table->foreign('request_id')->references('id')->on('recruitment_requests')->onDelete('cascade');
            $table->foreign('agency_id')->references('id')->on('agencies');
            $table->index(['request_id', 'agency_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_proposals');
    }
};