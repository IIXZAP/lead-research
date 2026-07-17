<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteAudit extends Model
{
    /** @use HasFactory<\Database\Factories\WebsiteAuditFactory> */
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'website_url',
        'final_url',
        'http_status',
        'https_enabled',
        'ssl_valid',
        'response_time_ms',
        'page_size_bytes',
        'mobile_viewport_found',
        'title_found',
        'meta_description_found',
        'contact_information_found',
        'contact_form_found',
        'broken_link_count',
        'redirect_count',
        'audit_score',
        'confidence_score',
        'issue_codes',
        'issues',
        'evidence',
        'raw_metrics',
        'audited_at',
    ];

    protected function casts(): array
    {
        return [
            'https_enabled' => 'boolean',
            'ssl_valid' => 'boolean',
            'mobile_viewport_found' => 'boolean',
            'title_found' => 'boolean',
            'meta_description_found' => 'boolean',
            'contact_information_found' => 'boolean',
            'contact_form_found' => 'boolean',
            'confidence_score' => 'decimal:3',
            'issue_codes' => 'array',
            'issues' => 'array',
            'evidence' => 'array',
            'raw_metrics' => 'array',
            'audited_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Lead, WebsiteAudit> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
