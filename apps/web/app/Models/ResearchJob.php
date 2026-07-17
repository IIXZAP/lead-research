<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResearchJob extends Model
{
    /** @use HasFactory<\Database\Factories\ResearchJobFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'campaign_id',
        'external_job_id',
        'status',
        'current_stage',
        'progress_percent',
        'total_items',
        'processed_items',
        'successful_items',
        'failed_items',
        'request_payload',
        'result_summary',
        'error_message',
        'attempt_count',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'result_summary' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Campaign, ResearchJob> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return HasMany<JobLog> */
    public function logs(): HasMany
    {
        return $this->hasMany(JobLog::class);
    }
}
