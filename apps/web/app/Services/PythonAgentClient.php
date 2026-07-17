<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Campaign;
use App\Models\ResearchJob;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * The Laravel -> Python half of the internal API contract (Requirement.md
 * §11). Signs every request with the same HMAC scheme the Python service
 * verifies in app/security/internal_auth.py, and that Laravel itself
 * verifies in VerifyInternalSignature for the reverse direction.
 */
class PythonAgentClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $sharedSecret,
        private readonly string $internalCallbackBaseUrl,
    ) {
    }

    /**
     * @throws PythonAgentException
     */
    public function startCampaign(Campaign $campaign, ResearchJob $researchJob): void
    {
        // Deliberately NOT route()/url() here — those build URLs from
        // APP_URL, which is the browser-facing host (e.g. http://localhost:8080).
        // The Python service runs in a separate container and has to reach
        // Laravel over the Docker network, where "localhost" means "the
        // celery-worker container itself" and connection-refuses every time.
        $callbackPath = "/api/internal/research-jobs/{$researchJob->id}/callback";

        $payload = [
            'campaign_id' => $campaign->id,
            'research_job_id' => $researchJob->id,
            'criteria' => $this->criteriaFromCampaign($campaign),
            'callback_url' => rtrim($this->internalCallbackBaseUrl, '/').$callbackPath,
        ];

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", $this->sharedSecret);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Internal-Key' => 'laravel',
                'X-Timestamp' => $timestamp,
                'X-Signature' => $signature,
                'X-Idempotency-Key' => (string) Str::uuid(),
            ])
                ->withBody($body, 'application/json')
                ->timeout(10)
                ->post("{$this->baseUrl}/internal/v1/campaigns/{$campaign->id}/process");
        } catch (ConnectionException $exception) {
            throw new PythonAgentException(
                "Could not reach the research service at {$this->baseUrl}: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        if ($response->failed()) {
            throw new PythonAgentException(
                "Research service rejected the campaign start request: HTTP {$response->status()} — {$response->body()}"
            );
        }
    }

    private function criteriaFromCampaign(Campaign $campaign): array
    {
        return [
            'business_keyword' => $campaign->business_keyword,
            'business_category' => $campaign->business_category,
            'province' => $campaign->province,
            'district' => $campaign->district,
            'location_text' => $campaign->location_text,
            'latitude' => $campaign->latitude !== null ? (float) $campaign->latitude : null,
            'longitude' => $campaign->longitude !== null ? (float) $campaign->longitude : null,
            'radius_km' => $campaign->radius_km,
            'maximum_leads' => $campaign->maximum_leads,
            'include_businesses_without_website' => $campaign->include_businesses_without_website,
            'include_businesses_with_website' => $campaign->include_businesses_with_website,
            'minimum_rating' => $campaign->minimum_rating !== null ? (float) $campaign->minimum_rating : null,
            'minimum_review_count' => $campaign->minimum_review_count,
            'country' => $campaign->country,
            'search_language' => $campaign->search_language,
        ];
    }
}
