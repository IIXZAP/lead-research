<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ResearchJob>
 */
class ResearchJobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'campaign_id' => Campaign::factory(),
            'status' => 'pending',
            'current_stage' => null,
            'progress_percent' => 0,
            'total_items' => 0,
            'processed_items' => 0,
            'successful_items' => 0,
            'failed_items' => 0,
            'attempt_count' => 0,
        ];
    }
}
