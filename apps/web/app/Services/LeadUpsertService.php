<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadStatus;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\WebsiteAudit;

/**
 * Upserts one lead from the Python callback payload, following the same
 * match priority as the DB's unique indexes (Requirement.md §7):
 * source_place_id, then normalized_phone, then normalized_domain. Never
 * fabricates a match on name/address alone — that fuzzy pass already
 * happened on the Python side, which tells us via `evidence.potential_duplicate`
 * whether a human should double check this one.
 */
class LeadUpsertService
{
    public function __construct(private readonly LeadNormalizer $normalizer)
    {
    }

    public function upsert(Campaign $campaign, array $leadPayload): Lead
    {
        $normalizedPhone = $this->normalizer->normalizePhone($leadPayload['phone'] ?? null);
        $normalizedDomain = $this->normalizer->normalizeDomain($leadPayload['website_url'] ?? null);
        $normalizedName = $this->normalizer->normalizeCompanyName($leadPayload['company_name']);

        $lead = $this->findExisting($campaign, $leadPayload, $normalizedPhone, $normalizedDomain);

        $attributes = [
            'campaign_id' => $campaign->id,
            'company_name' => $leadPayload['company_name'],
            'normalized_company_name' => $normalizedName,
            'phone' => $leadPayload['phone'] ?? null,
            'normalized_phone' => $normalizedPhone,
            'website_url' => $leadPayload['website_url'] ?? null,
            'normalized_domain' => $normalizedDomain,
            'address' => $leadPayload['address'] ?? null,
            'province' => $leadPayload['province'] ?? null,
            'business_type' => $leadPayload['business_type'] ?? null,
            'source' => $leadPayload['source'],
            'source_place_id' => $leadPayload['source_place_id'] ?? null,
            'raw_source_data' => $leadPayload['raw_source_data'] ?? [],
            'discovered_at' => $lead?->discovered_at ?? now(),
        ];

        if ($lead) {
            $lead->update($attributes);
        } else {
            $attributes['status'] = LeadStatus::New->value;
            $lead = Lead::create($attributes);
        }

        $this->recordAudit($lead, $leadPayload);

        return $lead->refresh();
    }

    private function findExisting(
        Campaign $campaign,
        array $leadPayload,
        ?string $normalizedPhone,
        ?string $normalizedDomain,
    ): ?Lead {
        $placeId = $leadPayload['source_place_id'] ?? null;

        if ($placeId) {
            $bySource = Lead::query()
                ->where('campaign_id', $campaign->id)
                ->where('source', $leadPayload['source'])
                ->where('source_place_id', $placeId)
                ->first();

            if ($bySource) {
                return $bySource;
            }
        }

        if ($normalizedPhone) {
            $byPhone = Lead::query()
                ->where('campaign_id', $campaign->id)
                ->where('normalized_phone', $normalizedPhone)
                ->first();

            if ($byPhone) {
                return $byPhone;
            }
        }

        if ($normalizedDomain) {
            $byDomain = Lead::query()
                ->where('campaign_id', $campaign->id)
                ->where('normalized_domain', $normalizedDomain)
                ->first();

            if ($byDomain) {
                return $byDomain;
            }
        }

        return null;
    }

    private function recordAudit(Lead $lead, array $leadPayload): void
    {
        if (! array_key_exists('audit_score', $leadPayload) && empty($leadPayload['issues'])) {
            return;
        }

        WebsiteAudit::create([
            'lead_id' => $lead->id,
            'website_url' => $leadPayload['website_url'] ?? null,
            'audit_score' => $leadPayload['audit_score'] ?? null,
            'confidence_score' => $leadPayload['confidence_score'] ?? null,
            'issue_codes' => $leadPayload['issue_codes'] ?? [],
            'issues' => $leadPayload['issues'] ?? [],
            'evidence' => $leadPayload['evidence'] ?? [],
            'audited_at' => now(),
        ]);
    }
}
