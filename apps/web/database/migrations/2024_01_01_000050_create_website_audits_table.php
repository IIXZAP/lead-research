<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('website_url')->nullable();
            $table->string('final_url')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->boolean('https_enabled')->nullable();
            $table->boolean('ssl_valid')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedBigInteger('page_size_bytes')->nullable();
            $table->boolean('mobile_viewport_found')->nullable();
            $table->boolean('title_found')->nullable();
            $table->boolean('meta_description_found')->nullable();
            $table->boolean('contact_information_found')->nullable();
            $table->boolean('contact_form_found')->nullable();
            $table->unsignedInteger('broken_link_count')->default(0);
            $table->unsignedInteger('redirect_count')->default(0);
            $table->unsignedTinyInteger('audit_score')->nullable();
            $table->decimal('confidence_score', 4, 3)->nullable();
            $table->json('issue_codes')->nullable();
            $table->json('issues')->nullable();
            $table->json('evidence')->nullable();
            $table->json('raw_metrics')->nullable();
            $table->timestamp('audited_at')->nullable();
            $table->timestamps();

            $table->index('lead_id');
            $table->index('audit_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_audits');
    }
};
