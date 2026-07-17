<?php

namespace Tests\Unit\Policies;

use App\Models\Campaign;
use App\Models\User;
use App\Policies\CampaignPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignPolicyTest extends TestCase
{
    use RefreshDatabase;

    private CampaignPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CampaignPolicy;
    }

    public function test_admin_can_create_update_and_delete_any_campaign(): void
    {
        $admin = User::factory()->admin()->create();
        $campaign = Campaign::factory()->create();

        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->update($admin, $campaign));
        $this->assertTrue($this->policy->delete($admin, $campaign));
    }

    public function test_sales_can_create_but_only_manage_their_own_campaigns(): void
    {
        $sales = User::factory()->sales()->create();
        $own = Campaign::factory()->for($sales)->create();
        $other = Campaign::factory()->create();

        $this->assertTrue($this->policy->create($sales));
        $this->assertTrue($this->policy->update($sales, $own));
        $this->assertFalse($this->policy->update($sales, $other));
        $this->assertFalse($this->policy->delete($sales, $own));
    }

    public function test_viewer_cannot_create_update_or_delete_campaigns(): void
    {
        $viewer = User::factory()->viewer()->create();
        $campaign = Campaign::factory()->create();

        $this->assertFalse($this->policy->create($viewer));
        $this->assertFalse($this->policy->update($viewer, $campaign));
        $this->assertFalse($this->policy->delete($viewer, $campaign));
        $this->assertFalse($this->policy->export($viewer, $campaign));
    }
}
