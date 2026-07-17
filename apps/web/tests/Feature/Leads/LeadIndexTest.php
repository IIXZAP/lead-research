<?php

namespace Tests\Feature\Leads;

use App\Enums\LeadStatus;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\User;
use App\Models\WebsiteAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_user_can_view_leads_for_their_own_campaign(): void
    {
        $sales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($sales)->create();
        Lead::factory()->for($campaign)->count(3)->create();

        $response = $this->actingAs($sales)->get(route('leads.index', $campaign));

        $response->assertOk();
        $response->assertViewHas('leads', fn ($leads) => $leads->total() === 3);
    }

    public function test_sales_user_cannot_view_leads_for_another_sales_users_campaign(): void
    {
        $owner = User::factory()->sales()->create();
        $otherSales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($owner)->create();

        $this->actingAs($otherSales)->get(route('leads.index', $campaign))->assertForbidden();
    }

    public function test_filtering_by_status(): void
    {
        $sales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($sales)->create();
        Lead::factory()->for($campaign)->create(['status' => LeadStatus::New->value]);
        Lead::factory()->for($campaign)->create(['status' => LeadStatus::Qualified->value]);

        $response = $this->actingAs($sales)->get(route('leads.index', $campaign).'?status=qualified');

        $response->assertOk();
        $response->assertViewHas('leads', fn ($leads) => $leads->total() === 1);
    }

    public function test_filtering_by_has_website(): void
    {
        $sales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($sales)->create();
        Lead::factory()->for($campaign)->create(['website_url' => null]);
        Lead::factory()->for($campaign)->create(['website_url' => 'https://example.com']);

        $response = $this->actingAs($sales)->get(route('leads.index', $campaign).'?has_website=0');

        $response->assertOk();
        $response->assertViewHas('leads', fn ($leads) => $leads->total() === 1 && $leads->first()->website_url === null);
    }

    public function test_filtering_by_audit_score_range(): void
    {
        $sales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($sales)->create();

        $lowScoreLead = Lead::factory()->for($campaign)->create();
        WebsiteAudit::factory()->for($lowScoreLead)->create(['audit_score' => 20]);

        $highScoreLead = Lead::factory()->for($campaign)->create();
        WebsiteAudit::factory()->for($highScoreLead)->create(['audit_score' => 90]);

        $response = $this->actingAs($sales)->get(route('leads.index', $campaign).'?max_score=50');

        $response->assertOk();
        $response->assertViewHas('leads', fn ($leads) => $leads->total() === 1);
    }
}
