<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\CallbackRequest;
use App\Models\ApiUsageLog;
use App\Models\JobLog;
use App\Models\ResearchJob;
use App\Services\LeadUpsertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InternalCallbackController extends Controller
{
    public function __construct(private readonly LeadUpsertService $leadUpsertService)
    {
    }

    public function store(CallbackRequest $request, string $job): JsonResponse
    {
        $data = $request->validated();

        /** @var ResearchJob|null $researchJob */
        $researchJob = ResearchJob::query()->with('campaign')->find($job);

        if (! $researchJob) {
            return response()->json(['message' => 'Unknown research job'], 404);
        }

        if ($researchJob->campaign_id !== (int) $data['campaign_id']) {
            return response()->json(['message' => 'campaign_id does not match research job'], 422);
        }

        DB::transaction(function () use ($data, $researchJob) {
            foreach ($data['leads'] ?? [] as $leadPayload) {
                $this->leadUpsertService->upsert($researchJob->campaign, $leadPayload);
            }

            foreach ($data['errors'] ?? [] as $error) {
                JobLog::create([
                    'research_job_id' => $researchJob->id,
                    'level' => 'error',
                    'stage' => $error['stage'] ?? null,
                    'message' => $error['message'] ?? 'Unknown error',
                    'context' => $error['context'] ?? [],
                ]);
            }

            foreach ($data['usage_logs'] ?? [] as $usageLog) {
                ApiUsageLog::create([
                    'provider' => $usageLog['provider'],
                    'endpoint' => $usageLog['endpoint'],
                    'campaign_id' => $researchJob->campaign_id,
                    'research_job_id' => $researchJob->id,
                    'request_hash' => $usageLog['request_hash'],
                    'response_status' => $usageLog['response_status'] ?? null,
                    'credit_used' => $usageLog['credit_used'] ?? 0,
                    'duration_ms' => $usageLog['duration_ms'] ?? null,
                    'is_cached' => $usageLog['is_cached'] ?? false,
                    'error_message' => $usageLog['error_message'] ?? null,
                    'requested_at' => $usageLog['requested_at'] ?? now(),
                ]);
            }

            $progress = $data['progress'];

            $researchJob->update([
                'status' => $data['status'],
                'total_items' => $progress['total_items'],
                'processed_items' => $progress['processed_items'],
                'successful_items' => $progress['successful_items'],
                'failed_items' => $progress['failed_items'],
                'progress_percent' => $progress['total_items'] > 0
                    ? (int) round(($progress['processed_items'] / $progress['total_items']) * 100)
                    : 100,
                'completed_at' => in_array($data['status'], ['completed', 'partially_completed', 'failed'], true)
                    ? now()
                    : null,
            ]);

            $this->syncCampaignStatus($researchJob, $data['status']);
        });

        return response()->json(['status' => 'ok']);
    }

    private function syncCampaignStatus(ResearchJob $researchJob, string $jobStatus): void
    {
        $campaignStatus = match ($jobStatus) {
            'processing' => CampaignStatus::Processing,
            'completed' => CampaignStatus::Completed,
            'partially_completed' => CampaignStatus::PartiallyCompleted,
            'failed' => CampaignStatus::Failed,
            default => null,
        };

        if ($campaignStatus === null) {
            return;
        }

        $researchJob->campaign->update([
            'status' => $campaignStatus->value,
            'completed_at' => in_array($jobStatus, ['completed', 'partially_completed', 'failed'], true)
                ? now()
                : null,
        ]);
    }
}
