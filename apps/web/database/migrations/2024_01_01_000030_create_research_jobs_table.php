<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('external_job_id')->nullable();
            $table->string('status')->default('pending');
            $table->string('current_stage')->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('processed_items')->default(0);
            $table->unsignedInteger('successful_items')->default(0);
            $table->unsignedInteger('failed_items')->default(0);
            $table->json('request_payload')->nullable();
            $table->json('result_summary')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_jobs');
    }
};
