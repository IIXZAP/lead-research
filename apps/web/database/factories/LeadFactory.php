<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        $hasWebsite = fake()->boolean(70);
        $companyName = fake()->company();

        return [
            'campaign_id' => Campaign::factory(),
            'company_name' => $companyName,
            'normalized_company_name' => str($companyName)->lower()->squish(),
            'phone' => fake()->boolean(80) ? fake()->numerify('0#-###-####') : null,
            'normalized_phone' => null,
            'website_url' => $hasWebsite ? 'https://'.fake()->domainName() : null,
            'normalized_domain' => null,
            'address' => fake()->address(),
            'province' => fake()->randomElement(['กรุงเทพมหานคร', 'สมุทรปราการ', 'เชียงใหม่']),
            'business_type' => fake()->word(),
            'rating' => fake()->randomFloat(1, 1, 5),
            'review_count' => fake()->numberBetween(0, 500),
            'source' => 'mock',
            'source_place_id' => fake()->unique()->uuid(),
            'status' => LeadStatus::New->value,
            'raw_source_data' => [],
            'discovered_at' => now(),
        ];
    }
}
