<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'company_name',
        'normalized_company_name',
        'phone',
        'normalized_phone',
        'website_url',
        'normalized_domain',
        'address',
        'province',
        'district',
        'business_type',
        'rating',
        'review_count',
        'latitude',
        'longitude',
        'source',
        'source_place_id',
        'source_data_id',
        'status',
        'assigned_user_id',
        'raw_source_data',
        'discovered_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'raw_source_data' => 'array',
            'rating' => 'decimal:1',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'discovered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Campaign, Lead> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return BelongsTo<User, Lead> */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /** @return HasOne<WebsiteAudit> */
    public function latestAudit(): HasOne
    {
        return $this->hasOne(WebsiteAudit::class)->latestOfMany('audited_at');
    }

    /** @return HasMany<WebsiteAudit> */
    public function audits(): HasMany
    {
        return $this->hasMany(WebsiteAudit::class);
    }

    /** @return HasMany<LeadNote> */
    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class);
    }
}
