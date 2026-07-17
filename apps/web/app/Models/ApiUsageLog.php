<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiUsageLog extends Model
{
    /** @use HasFactory<\Database\Factories\ApiUsageLogFactory> */
    use HasFactory;

    protected $fillable = [
        'provider',
        'endpoint',
        'campaign_id',
        'research_job_id',
        'request_hash',
        'response_status',
        'credit_used',
        'duration_ms',
        'is_cached',
        'error_message',
        'requested_at',
    ];

    protected function casts(): array
    {
        return [
            'is_cached' => 'boolean',
            'requested_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Campaign, ApiUsageLog> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
