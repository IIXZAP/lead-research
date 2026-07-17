<?php

namespace Tests\Feature\Leads;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_user_can_add_a_note_to_a_lead_on_their_own_campaign(): void
    {
        $sales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($sales)->create();
        $lead = Lead::factory()->for($campaign)->create();

        $response = $this->actingAs($sales)->post(route('leads.notes.store', $lead), [
            'note' => 'ติดต่อไปแล้ว รอนัดหมาย',
        ]);

        $response->assertRedirect(route('leads.show', $lead));
        $this->assertDatabaseHas('lead_notes', [
            'lead_id' => $lead->id,
            'user_id' => $sales->id,
            'note' => 'ติดต่อไปแล้ว รอนัดหมาย',
        ]);
    }

    public function test_viewer_cannot_add_a_note(): void
    {
        $viewer = User::factory()->viewer()->create();
        $lead = Lead::factory()->create();

        $this->actingAs($viewer)
            ->post(route('leads.notes.store', $lead), ['note' => 'test'])
            ->assertForbidden();

        $this->assertDatabaseCount('lead_notes', 0);
    }

    public function test_note_is_required(): void
    {
        $sales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($sales)->create();
        $lead = Lead::factory()->for($campaign)->create();

        $response = $this->actingAs($sales)->post(route('leads.notes.store', $lead), ['note' => '']);

        $response->assertSessionHasErrors('note');
    }
}
