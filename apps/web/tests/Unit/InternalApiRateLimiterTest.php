<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class InternalApiRateLimiterTest extends TestCase
{
    public function test_internal_api_limiter_allows_120_per_minute_scoped_by_ip(): void
    {
        $limiter = RateLimiter::limiter('internal-api');
        $this->assertNotNull($limiter, 'internal-api rate limiter is not registered');

        $request = Request::create('/api/internal/research-jobs/abc/callback', 'POST');
        $request->server->set('REMOTE_ADDR', '203.0.113.5');

        $limit = $limiter($request);

        $this->assertSame(120, $limit->maxAttempts);
    }

    public function test_campaign_export_limiter_allows_10_per_minute(): void
    {
        $limiter = RateLimiter::limiter('campaign-export');
        $this->assertNotNull($limiter, 'campaign-export rate limiter is not registered');

        $request = Request::create('/campaigns/1/leads/export', 'GET');
        $limit = $limiter($request);

        $this->assertSame(10, $limit->maxAttempts);
    }
}
