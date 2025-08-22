<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id');
            $table->uuid('nationality_id');
            $table->uuid('profession_id');
            $table->integer('quantity');
            $table->integer('sla_days');
            $table->enum('status', ['Open', 'PartiallyAwarded', 'FullyAwarded'])->default('Open');
            $table->text('requirements')->nullable();
            $table->date('deadline');
            $table->uuid('created_by')->nullable();
            $table->uuid('modified_by')->nullable();
            $table->timestamps();
            
            $table->foreign('country_id')->references('id')->on('countries');
            $table->foreign('nationality_id')->references('id')->on('nationalities');
            $table->foreign('profession_id')->references('id')->on('professions');
            $table->index(['status', 'country_id', 'nationality_id', 'profession_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_requests');
    }
};