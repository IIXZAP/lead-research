<?php

namespace Tests\Feature\Internal;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\ResearchJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class CallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['internal.shared_secret' => 'test-shared-secret', 'internal.signature_ttl_seconds' => 300]);

        // The array cache driver persists across test methods within a
        // single run (only the database is reset by RefreshDatabase), so
        // any idempotency key remembered by one test would otherwise leak
        // into the next and cause spurious 409s.
        Cache::flush();
    }

    private function signedHeaders(string $body, string $idempotencyKey): array
    {
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", 'test-shared-secret');

        return [
            'X-Internal-Key' => 'research-agent',
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
            'X-Idempotency-Key' => $idempotencyKey,
            'Content-Type' => 'application/json',
        ];
    }

    private function postSigned(string $url, array $payload, array $headerOverrides = []): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload);
        $headers = array_merge(
            $this->signedHeaders($body, $headerOverrides['idempotency_key'] ?? (string) Str::uuid()),
            $headerOverrides
        );

        return $this->call('POST', $url, [], [], [], $this->transformHeadersToServerVars($headers), $body);
    }

    public function test_callback_is_rejected_without_signature_headers(): void
    {
        $campaign = Campaign::factory()->create();
        $job = ResearchJob::factory()->for($campaign)->create();

        $response = $this->postJson("/api/internal/research-jobs/{$job->id}/callback", ['job_id' => $job->id]);

        $response->assertStatus(401);
    }

    public function test_callback_is_rejected_with_invalid_signature(): void
    {
        $campaign = Campaign::factory()->create();
        $job = ResearchJob::factory()->for($campaign)->create();

        $response = $this->postSigned("/api/internal/research-jobs/{$job->id}/callback", [
            'job_id' => $job->id,
            'campaign_id' => $campaign->id,
            'status' => 'completed',
            'progress' => ['total_items' => 0, 'processed_items' => 0, 'successful_items' => 0, 'failed_items' => 0],
        ], ['X-Signature' => 'tampered']);

        $response->assertStatus(401);
    }

    public function test_replayed_idempotency_key_is_rejected(): void
    {
        $campaign = Campaign::factory()->create();
        $job = ResearchJob::factory()->for($campaign)->create();

        $payload = [
            'job_id' => $job->id,
            'campaign_id' => $campaign->id,
            'status' => 'completed',
            'progress' => ['total_items' => 0, 'processed_items' => 0, 'successful_items' => 0, 'failed_items' => 0],
        ];

        $first = $this->postSigned("/api/internal/research-jobs/{$job->id}/callback", $payload, ['idempotency_key' => 'same-key']);
        $second = $this->postSigned("/api/internal/research-jobs/{$job->id}/callback", $payload, ['idempotency_key' => 'same-key']);

        $first->assertOk();
        $second->assertStatus(409);
    }

    public function test_callback_upserts_a_new_lead(): void
    {
        $campaign = Campaign::factory()->create();
        $job = ResearchJob::factory()->for($campaign)->create();

        $payload = [
            'job_id' => $job->id,
            'campaign_id' => $campaign->id,
            'status' => 'completed',
            'progress' => ['total_items' => 1, 'processed_items' => 1, 'successful_items' => 1, 'failed_items' => 0],
            'leads' => [[
                'company_name' => 'บริษัท ตัวอย่าง จำกัด',
                'phone' => '02-000-0000',
                'website_url' => 'https://example.com',
                'source' => 'mock',
                'source_place_id' => 'place-001',
                'website_issue' => 'ไม่พบเว็บไซต์ของบริษัท',
                'audit_score' => 80,
                'confidence_score' => 0.9,
            ]],
        ];

        $response = $this->postSigned("/api/internal/research-jobs/{$job->id}/callback", $payload);

        $response->assertOk();
        $this->assertDatabaseHas('leads', [
            'campaign_id' => $campaign->id,
            'company_name' => 'บริษัท ตัวอย่าง จำกัด',
            'normalized_phone' => '020000000',
        ]);
        $this->assertDatabaseHas('website_audits', ['audit_score' => 80]);
    }

    public function test_callback_upserts_same_lead_on_repeat_place_id_instead_of_duplicating(): void
    {
        $campaign = Campaign::factory()->create();
        $job = ResearchJob::factory()->for($campaign)->create();

        $leadData = [
            'company_name' => 'บริษัท ตัวอย่าง จำกัด',
            'phone' => '02-000-0000',
            'source' => 'mock',
            'source_place_id' => 'place-001',
            'website_issue' => 'ไม่พบเว็บไซต์ของบริษัท',
        ];

        $basePayload = [
            'job_id' => $job->id,
            'campaign_id' => $campaign->id,
            'status' => 'processing',
            'progress' => ['total_items' => 1, 'processed_items' => 1, 'successful_items' => 1, 'failed_items' => 0],
        ];

        $this->postSigned("/api/internal/research-jobs/{$job->id}/callback", [...$basePayload, 'leads' => [$leadData]], ['idempotency_key' => 'first']);
        $this->postSigned("/api/internal/research-jobs/{$job->id}/callback", [...$basePayload, 'leads' => [$leadData]], ['idempotency_key' => 'second']);

        $this->assertDatabaseCount('leads', 1);
    }

    public function test_job_and_campaign_status_transitions_to_partially_completed(): void
    {
        $campaign = Campaign::factory()->create();
        $job = ResearchJob::factory()->for($campaign)->create();

        $payload = [
            'job_id' => $job->id,
            'campaign_id' => $campaign->id,
            'status' => 'partially_completed',
            'progress' => ['total_items' => 10, 'processed_items' => 10, 'successful_items' => 8, 'failed_items' => 2],
            'errors' => [['stage' => 'website_audit', 'message' => 'timeout', 'context' => []]],
        ];

        $response = $this->postSigned("/api/internal/research-jobs/{$job->id}/callback", $payload);

        $response->assertOk();
        $this->assertDatabaseHas('research_jobs', ['id' => $job->id, 'status' => 'partially_completed', 'failed_items' => 2]);
        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id, 'status' => 'partially_completed']);
        $this->assertDatabaseHas('job_logs', ['research_job_id' => $job->id, 'message' => 'timeout']);
    }

    public function test_callback_for_unknown_job_returns_404(): void
    {
        $campaign = Campaign::factory()->create();

        $response = $this->postSigned('/api/internal/research-jobs/does-not-exist/callback', [
            'job_id' => 'does-not-exist',
            'campaign_id' => $campaign->id,
            'status' => 'completed',
            'progress' => ['total_items' => 0, 'processed_items' => 0, 'successful_items' => 0, 'failed_items' => 0],
        ]);

        $response->assertStatus(404);
    }

    public function test_callback_persists_usage_logs(): void
    {
        $campaign = Campaign::factory()->create();
        $job = ResearchJob::factory()->for($campaign)->create();

        $payload = [
            'job_id' => $job->id,
            'campaign_id' => $campaign->id,
            'status' => 'completed',
            'progress' => ['total_items' => 0, 'processed_items' => 0, 'successful_items' => 0, 'failed_items' => 0],
            'usage_logs' => [[
                'provider' => 'serpapi',
                'endpoint' => 'google_maps',
                'request_hash' => 'abc123',
                'response_status' => 200,
                'credit_used' => 1,
                'duration_ms' => 350,
                'is_cached' => false,
                'requested_at' => now()->toIso8601String(),
            ]],
        ];

        $response = $this->postSigned("/api/internal/research-jobs/{$job->id}/callback", $payload);

        $response->assertOk();
        $this->assertDatabaseHas('api_usage_logs', [
            'campaign_id' => $campaign->id,
            'research_job_id' => $job->id,
            'provider' => 'serpapi',
            'request_hash' => 'abc123',
            'credit_used' => 1,
        ]);
    }
}
