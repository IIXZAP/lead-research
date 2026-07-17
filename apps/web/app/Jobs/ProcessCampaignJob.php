<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\ResearchJob;
use App\Services\PythonAgentClient;
use App\Services\PythonAgentException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [5, 15, 60];

    public function __construct(
        public readonly Campaign $campaign,
        public readonly ResearchJob $researchJob,
    ) {
    }

    public function handle(PythonAgentClient $client): void
    {
        try {
            $client->startCampaign($this->campaign, $this->researchJob);
        } catch (PythonAgentException $exception) {
            // Let Laravel's queue retry/backoff handle transient failures
            // (research service briefly unreachable); only give up and
            // mark everything failed once retries are exhausted, in failed().
            report($exception);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->researchJob->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'completed_at' => now(),
        ]);

        $this->campaign->update([
            'status' => CampaignStatus::Failed->value,
            'completed_at' => now(),
        ]);
    }
}
