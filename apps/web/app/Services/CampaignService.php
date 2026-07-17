<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\CampaignStatus;
use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use App\Models\ResearchJob;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CampaignService
{
    /**
     * Scope the campaign list per Requirement.md §3:
     * admin sees everything, sales sees only their own, viewer sees
     * only campaigns they've been explicitly granted (not modeled yet
     * in Phase 3 — falls back to none until a grants table exists).
     */
    public function listFor(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $this->baseQuery($user)
            ->withCount('leads')
            ->latest()
            ->paginate($perPage);
    }

    private function baseQuery(User $user): Builder
    {
        return match ($user->account_type) {
            AccountType::Admin => Campaign::query(),
            AccountType::Sales => Campaign::query()->where('user_id', $user->id),
            AccountType::Viewer => Campaign::query()->whereRaw('1 = 0'),
        };
    }

    public function create(User $user, array $data): Campaign
    {
        return Campaign::create([
            ...$data,
            'user_id' => $user->id,
            'status' => CampaignStatus::Draft->value,
        ]);
    }

    public function update(Campaign $campaign, array $data): Campaign
    {
        $campaign->update($data);

        return $campaign->refresh();
    }

    public function delete(Campaign $campaign): void
    {
        $campaign->delete();
    }

    /**
     * Creates the ResearchJob row and hands off to a queued job so the
     * HTTP request to Python happens off the request/response cycle —
     * a slow or unreachable research service should never make the
     * "Start" button hang.
     */
    public function start(Campaign $campaign): ResearchJob
    {
        $researchJob = ResearchJob::create([
            'campaign_id' => $campaign->id,
            'status' => 'pending',
            'attempt_count' => 0,
        ]);

        $campaign->update([
            'status' => CampaignStatus::Queued->value,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        ProcessCampaignJob::dispatch($campaign, $researchJob);

        return $researchJob;
    }

    /**
     * Best-effort cancellation: marks the campaign cancelled locally.
     * Phase 5 does not yet call back into Python to actually stop an
     * in-flight Celery task — a job already running there will finish
     * and its callback will simply be ignored once the campaign is
     * no longer `processing`/`queued`.
     */
    public function cancel(Campaign $campaign): Campaign
    {
        $campaign->update([
            'status' => CampaignStatus::Cancelled->value,
            'completed_at' => now(),
        ]);

        return $campaign->refresh();
    }
}
