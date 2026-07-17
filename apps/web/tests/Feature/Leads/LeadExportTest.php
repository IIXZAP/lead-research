<?php

namespace Tests\Feature\Leads;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_user_can_export_leads_for_their_own_campaign(): void
    {
        $sales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($sales)->create();
        Lead::factory()->for($campaign)->create(['company_name' => 'Export Test Co']);

        $response = $this->actingAs($sales)->get(route('leads.export', $campaign));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Export Test Co', $content);
        $this->assertStringContainsString('Company Name', $content);
    }

    public function test_viewer_cannot_export_leads(): void
    {
        $viewer = User::factory()->viewer()->create();
        $campaign = Campaign::factory()->create();

        $this->actingAs($viewer)->get(route('leads.export', $campaign))->assertForbidden();
    }

    public function test_sales_user_cannot_export_another_sales_users_campaign(): void
    {
        $owner = User::factory()->sales()->create();
        $otherSales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($owner)->create();

        $this->actingAs($otherSales)->get(route('leads.export', $campaign))->assertForbidden();
    }

    public function test_export_respects_status_filter(): void
    {
        $sales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($sales)->create();
        Lead::factory()->for($campaign)->create(['company_name' => 'New Lead Co', 'status' => 'new']);
        Lead::factory()->for($campaign)->create(['company_name' => 'Qualified Lead Co', 'status' => 'qualified']);

        $response = $this->actingAs($sales)->get(route('leads.export', $campaign).'?status=qualified');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Qualified Lead Co', $content);
        $this->assertStringNotContainsString('New Lead Co', $content);
    }
}
