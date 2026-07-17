<?php

namespace Tests\Feature\Campaigns;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_user_cannot_view_another_sales_users_campaign(): void
    {
        $owner = User::factory()->sales()->create();
        $otherSales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($owner)->create();

        $this->actingAs($otherSales)->get(route('campaigns.show', $campaign))->assertForbidden();
    }

    public function test_sales_user_cannot_update_another_sales_users_campaign(): void
    {
        $owner = User::factory()->sales()->create();
        $otherSales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($owner)->create();

        $this->actingAs($otherSales)
            ->put(route('campaigns.update', $campaign), ['name' => 'Hijacked', 'business_keyword' => 'x'])
            ->assertForbidden();

        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id, 'name' => 'Hijacked']);
    }

    public function test_admin_can_view_any_sales_users_campaign(): void
    {
        $owner = User::factory()->sales()->create();
        $admin = User::factory()->admin()->create();
        $campaign = Campaign::factory()->for($owner)->create();

        $this->actingAs($admin)->get(route('campaigns.show', $campaign))->assertOk();
    }

    public function test_sales_users_campaign_list_only_shows_their_own_campaigns(): void
    {
        $owner = User::factory()->sales()->create();
        $otherSales = User::factory()->sales()->create();

        Campaign::factory()->for($owner)->count(2)->create();
        Campaign::factory()->for($otherSales)->count(3)->create();

        $response = $this->actingAs($owner)->get(route('campaigns.index'));

        $response->assertOk();
        $response->assertViewHas('campaigns', fn ($campaigns) => $campaigns->total() === 2);
    }
}
