<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebsiteAudit>
 */
class WebsiteAuditFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'website_url' => 'https://'.fake()->domainName(),
            'final_url' => 'https://'.fake()->domainName(),
            'http_status' => 200,
            'https_enabled' => fake()->boolean(80),
            'ssl_valid' => fake()->boolean(90),
            'response_time_ms' => fake()->numberBetween(200, 5000),
            'page_size_bytes' => fake()->numberBetween(10000, 500000),
            'mobile_viewport_found' => fake()->boolean(70),
            'title_found' => fake()->boolean(90),
            'meta_description_found' => fake()->boolean(70),
            'contact_information_found' => fake()->boolean(60),
            'contact_form_found' => fake()->boolean(50),
            'broken_link_count' => fake()->numberBetween(0, 5),
            'redirect_count' => fake()->numberBetween(0, 2),
            'audit_score' => fake()->numberBetween(20, 100),
            'confidence_score' => fake()->randomFloat(3, 0.5, 1),
            'issue_codes' => [],
            'issues' => [],
            'evidence' => [],
            'raw_metrics' => [],
            'audited_at' => now(),
        ];
    }
}
