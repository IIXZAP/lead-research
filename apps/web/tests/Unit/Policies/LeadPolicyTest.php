<?php

namespace Tests\Unit\Policies;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\User;
use App\Policies\LeadPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadPolicyTest extends TestCase
{
    use RefreshDatabase;

    private LeadPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new LeadPolicy;
    }

    public function test_sales_can_manage_leads_only_on_their_own_campaigns(): void
    {
        $sales = User::factory()->sales()->create();
        $ownCampaign = Campaign::factory()->for($sales)->create();
        $otherCampaign = Campaign::factory()->create();

        $ownLead = Lead::factory()->for($ownCampaign)->create();
        $otherLead = Lead::factory()->for($otherCampaign)->create();

        $this->assertTrue($this->policy->updateStatus($sales, $ownLead));
        $this->assertTrue($this->policy->addNote($sales, $ownLead));
        $this->assertTrue($this->policy->export($sales, $ownLead));

        $this->assertFalse($this->policy->updateStatus($sales, $otherLead));
        $this->assertFalse($this->policy->addNote($sales, $otherLead));
        $this->assertFalse($this->policy->export($sales, $otherLead));
    }

    public function test_viewer_can_view_but_cannot_change_or_export_leads(): void
    {
        $viewer = User::factory()->viewer()->create();
        $lead = Lead::factory()->create();

        $this->assertTrue($this->policy->view($viewer, $lead));
        $this->assertFalse($this->policy->updateStatus($viewer, $lead));
        $this->assertFalse($this->policy->addNote($viewer, $lead));
        $this->assertFalse($this->policy->export($viewer, $lead));
    }

    public function test_admin_can_manage_any_lead(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = Lead::factory()->create();

        $this->assertTrue($this->policy->updateStatus($admin, $lead));
        $this->assertTrue($this->policy->export($admin, $lead));
    }
}
