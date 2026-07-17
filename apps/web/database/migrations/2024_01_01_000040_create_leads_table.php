<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('normalized_company_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('normalized_phone')->nullable();
            $table->string('website_url')->nullable();
            $table->string('normalized_domain')->nullable();
            $table->text('address')->nullable();
            $table->string('province')->nullable();
            $table->string('district')->nullable();
            $table->string('business_type')->nullable();
            $table->decimal('rating', 2, 1)->nullable();
            $table->unsignedInteger('review_count')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('source')->nullable();
            $table->string('source_place_id')->nullable();
            $table->string('source_data_id')->nullable();
            $table->string('status')->default('new');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('raw_source_data')->nullable();
            $table->timestamp('discovered_at')->nullable();
            $table->timestamps();

            // MySQL treats each NULL as distinct within a unique index, so
            // leads legitimately missing a phone/domain/place id never
            // collide with each other here — see Requirement.md §5.
            $table->unique(['campaign_id', 'source', 'source_place_id'], 'leads_campaign_source_place_unique');
            $table->unique(['campaign_id', 'normalized_phone'], 'leads_campaign_phone_unique');
            $table->unique(['campaign_id', 'normalized_domain'], 'leads_campaign_domain_unique');
            $table->index(['campaign_id', 'status']);
            $table->index('normalized_company_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
