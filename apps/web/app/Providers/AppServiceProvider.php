<?php

namespace App\Providers;

use App\Services\PythonAgentClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PythonAgentClient::class, function () {
            return new PythonAgentClient(
                baseUrl: config('internal.research_agent_base_url'),
                sharedSecret: config('internal.shared_secret'),
                internalCallbackBaseUrl: config('internal.internal_callback_base_url'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Defense-in-depth on top of HMAC verification: even a correctly
        // signed flood of callback requests (e.g. a compromised or buggy
        // research-agent deployment) shouldn't be able to hammer the DB
        // unbounded. Scoped by IP since there's no per-caller identity
        // beyond the shared secret.
        RateLimiter::for('internal-api', function ($request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        // CSV export runs a full unpaginated query per request — worth
        // its own, tighter limit independent of normal page-browsing.
        RateLimiter::for('campaign-export', function ($request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
