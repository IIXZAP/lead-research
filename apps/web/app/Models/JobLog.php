<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'research_job_id',
        'level',
        'stage',
        'message',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ResearchJob, JobLog> */
    public function researchJob(): BelongsTo
    {
        return $this->belongsTo(ResearchJob::class);
    }
}
