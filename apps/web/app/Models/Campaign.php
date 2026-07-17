<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'business_keyword',
        'business_category',
        'province',
        'district',
        'location_text',
        'latitude',
        'longitude',
        'radius_km',
        'maximum_leads',
        'include_businesses_without_website',
        'include_businesses_with_website',
        'minimum_rating',
        'minimum_review_count',
        'search_language',
        'country',
        'status',
        'settings',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'settings' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'minimum_rating' => 'decimal:1',
            'include_businesses_without_website' => 'boolean',
            'include_businesses_with_website' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, Campaign> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ResearchJob> */
    public function researchJobs(): HasMany
    {
        return $this->hasMany(ResearchJob::class);
    }

    /** @return HasMany<Lead> */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /** @return HasMany<ApiUsageLog> */
    public function apiUsageLogs(): HasMany
    {
        return $this->hasMany(ApiUsageLog::class);
    }
}
