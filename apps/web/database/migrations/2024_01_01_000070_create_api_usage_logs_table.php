<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('endpoint');
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('research_job_id')->nullable();
            $table->string('request_hash')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->unsignedInteger('credit_used')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('is_cached')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'requested_at']);
            $table->index('request_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_usage_logs');
    }
};
