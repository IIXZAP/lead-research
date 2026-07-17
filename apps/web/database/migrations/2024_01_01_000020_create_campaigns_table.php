<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('business_keyword');
            $table->string('business_category')->nullable();
            $table->string('province')->nullable();
            $table->string('district')->nullable();
            $table->string('location_text')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('radius_km')->default(10);
            $table->unsignedInteger('maximum_leads')->default(100);
            $table->boolean('include_businesses_without_website')->default(true);
            $table->boolean('include_businesses_with_website')->default(true);
            $table->decimal('minimum_rating', 2, 1)->nullable();
            $table->unsignedInteger('minimum_review_count')->nullable();
            $table->string('search_language', 10)->default('th');
            $table->string('country', 2)->default('th');
            $table->string('status')->default('draft');
            $table->json('settings')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
