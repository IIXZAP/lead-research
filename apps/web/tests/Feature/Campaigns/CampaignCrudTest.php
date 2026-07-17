<?php

namespace Tests\Feature\Campaigns;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_user_can_create_a_campaign(): void
    {
        $sales = User::factory()->sales()->create();

        $response = $this->actingAs($sales)->post(route('campaigns.store'), [
            'name' => 'โรงงานอาหารสมุทรปราการ',
            'business_keyword' => 'โรงงานผลิตอาหาร',
            'province' => 'สมุทรปราการ',
            'radius_km' => 20,
            'maximum_leads' => 100,
        ]);

        $this->assertDatabaseHas('campaigns', [
            'name' => 'โรงงานอาหารสมุทรปราการ',
            'user_id' => $sales->id,
            'status' => CampaignStatus::Draft->value,
        ]);

        $campaign = Campaign::query()->first();
        $response->assertRedirect(route('campaigns.show', $campaign));
    }

    public function test_viewer_cannot_create_a_campaign(): void
    {
        $viewer = User::factory()->viewer()->create();

        $response = $this->actingAs($viewer)->post(route('campaigns.store'), [
            'name' => 'Test',
            'business_keyword' => 'test',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_sales_user_can_update_their_own_campaign(): void
    {
        $sales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($sales)->create(['name' => 'Old name']);

        $response = $this->actingAs($sales)->put(route('campaigns.update', $campaign), [
            'name' => 'New name',
            'business_keyword' => $campaign->business_keyword,
        ]);

        $response->assertRedirect(route('campaigns.show', $campaign));
        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id, 'name' => 'New name']);
    }

    public function test_only_admin_can_delete_a_campaign(): void
    {
        $sales = User::factory()->sales()->create();
        $admin = User::factory()->admin()->create();
        $campaign = Campaign::factory()->for($sales)->create();

        $this->actingAs($sales)->delete(route('campaigns.destroy', $campaign))->assertForbidden();
        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);

        $this->actingAs($admin)->delete(route('campaigns.destroy', $campaign))->assertRedirect(route('campaigns.index'));
        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }
}
