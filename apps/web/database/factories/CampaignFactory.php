<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company().' campaign',
            'business_keyword' => fake()->randomElement(['โรงงานผลิตอาหาร', 'ร้านอาหาร', 'คลินิกความงาม', 'บริษัทขนส่ง']),
            'business_category' => fake()->word(),
            'province' => fake()->randomElement(['กรุงเทพมหานคร', 'สมุทรปราการ', 'เชียงใหม่', 'ชลบุรี']),
            'district' => fake()->citySuffix(),
            'location_text' => null,
            'latitude' => fake()->latitude(5, 20),
            'longitude' => fake()->longitude(97, 105),
            'radius_km' => 10,
            'maximum_leads' => 100,
            'include_businesses_without_website' => true,
            'include_businesses_with_website' => true,
            'minimum_rating' => null,
            'minimum_review_count' => null,
            'search_language' => 'th',
            'country' => 'th',
            'status' => CampaignStatus::Draft->value,
            'settings' => [],
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => CampaignStatus::Completed->value,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }
}
