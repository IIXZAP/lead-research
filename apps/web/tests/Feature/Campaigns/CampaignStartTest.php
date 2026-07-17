<?php

namespace Tests\Feature\Campaigns;

use App\Enums\CampaignStatus;
use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use App\Models\User;
use App\Services\PythonAgentClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CampaignStartTest extends TestCase
{
    use RefreshDatabase;

    public function test_starting_a_campaign_creates_a_research_job_and_dispatches_the_processing_job(): void
    {
        Queue::fake();

        $sales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($sales)->create(['status' => CampaignStatus::Draft->value]);

        $response = $this->actingAs($sales)->post(route('campaigns.start', $campaign));

        $response->assertRedirect(route('campaigns.show', $campaign));
        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id, 'status' => CampaignStatus::Queued->value]);
        $this->assertDatabaseHas('research_jobs', ['campaign_id' => $campaign->id, 'status' => 'pending']);

        Queue::assertPushed(ProcessCampaignJob::class, function (ProcessCampaignJob $job) use ($campaign) {
            return $job->campaign->id === $campaign->id;
        });
    }

    public function test_viewer_cannot_start_a_campaign(): void
    {
        Queue::fake();

        $viewer = User::factory()->viewer()->create();
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Draft->value]);

        $this->actingAs($viewer)->post(route('campaigns.start', $campaign))->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_sales_cannot_start_another_sales_users_campaign(): void
    {
        Queue::fake();

        $owner = User::factory()->sales()->create();
        $otherSales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($owner)->create(['status' => CampaignStatus::Draft->value]);

        $this->actingAs($otherSales)->post(route('campaigns.start', $campaign))->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_cancelling_a_processing_campaign_marks_it_cancelled(): void
    {
        $sales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($sales)->create(['status' => CampaignStatus::Processing->value]);

        $response = $this->actingAs($sales)->post(route('campaigns.cancel', $campaign));

        $response->assertRedirect(route('campaigns.show', $campaign));
        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id, 'status' => CampaignStatus::Cancelled->value]);
    }

    public function test_process_campaign_job_calls_the_python_agent_client(): void
    {
        $sales = User::factory()->sales()->create();
        $campaign = Campaign::factory()->for($sales)->create();
        $researchJob = \App\Models\ResearchJob::factory()->for($campaign)->create();

        $mockClient = \Mockery::mock(PythonAgentClient::class);
        $mockClient->shouldReceive('startCampaign')->once();

        $job = new ProcessCampaignJob($campaign, $researchJob);
        $job->handle($mockClient);
    }
}
