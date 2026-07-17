<?php

namespace Tests\Feature\Leads;

use App\Enums\LeadStatus;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_user_can_update_status_of_a_lead_on_their_own_campaign(): void
    {
        $sales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($sales)->create();
        $lead = Lead::factory()->for($campaign)->create(['status' => LeadStatus::New->value]);

        $response = $this->actingAs($sales)->patch(route('leads.update-status', $lead), [
            'status' => LeadStatus::Qualified->value,
        ]);

        $response->assertRedirect(route('leads.show', $lead));
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => LeadStatus::Qualified->value]);
    }

    public function test_sales_user_cannot_update_status_of_a_lead_on_another_sales_users_campaign(): void
    {
        $owner = User::factory()->sales()->create();
        $otherSales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($owner)->create();
        $lead = Lead::factory()->for($campaign)->create(['status' => LeadStatus::New->value]);

        $this->actingAs($otherSales)
            ->patch(route('leads.update-status', $lead), ['status' => LeadStatus::Qualified->value])
            ->assertForbidden();

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => LeadStatus::New->value]);
    }

    public function test_viewer_cannot_update_lead_status(): void
    {
        $viewer = User::factory()->viewer()->create();
        $lead = Lead::factory()->create(['status' => LeadStatus::New->value]);

        $this->actingAs($viewer)
            ->patch(route('leads.update-status', $lead), ['status' => LeadStatus::Qualified->value])
            ->assertForbidden();
    }

    public function test_invalid_status_value_is_rejected(): void
    {
        $sales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($sales)->create();
        $lead = Lead::factory()->for($campaign)->create();

        $response = $this->actingAs($sales)->patch(route('leads.update-status', $lead), [
            'status' => 'not_a_real_status',
        ]);

        $response->assertSessionHasErrors('status');
    }
}
