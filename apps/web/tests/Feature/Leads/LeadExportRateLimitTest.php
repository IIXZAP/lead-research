<?php

namespace Tests\Feature\Leads;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadExportRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_is_rate_limited_after_ten_requests_per_minute(): void
    {
        $sales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($sales)->create();

        $this->actingAs($sales);

        for ($i = 0; $i < 10; $i++) {
            $this->get(route('leads.export', $campaign))->assertOk();
        }

        $this->get(route('leads.export', $campaign))->assertStatus(429);
    }
}
