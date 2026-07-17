<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiUsageLog>
 */
class ApiUsageLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provider' => 'serpapi',
            'endpoint' => 'google_maps',
            'campaign_id' => Campaign::factory(),
            'research_job_id' => null,
            'request_hash' => fake()->sha256(),
            'response_status' => 200,
            'credit_used' => 1,
            'duration_ms' => fake()->numberBetween(100, 2000),
            'is_cached' => fake()->boolean(20),
            'requested_at' => now(),
        ];
    }
}
