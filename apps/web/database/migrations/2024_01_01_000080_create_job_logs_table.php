<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('research_job_id');
            $table->string('level')->default('info');
            $table->string('stage')->nullable();
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('research_job_id')->references('id')->on('research_jobs')->cascadeOnDelete();
            $table->index(['research_job_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_logs');
    }
};
