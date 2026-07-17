<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadStatus;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadService
{
    /**
     * Filters supported (Requirement.md §16 "CSV export filters" implies
     * the same filter set drives both the browse view and the export):
     * status, has_website, min_score, max_score, province, search.
     */
    public function listForCampaign(Campaign $campaign, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->filteredQuery($campaign, $filters)
            ->with('latestAudit')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function exportForCampaign(Campaign $campaign, array $filters): Collection
    {
        return $this->filteredQuery($campaign, $filters)->with('latestAudit')->latest()->get();
    }

    private function filteredQuery(Campaign $campaign, array $filters): Builder
    {
        return Lead::query()
            ->where('campaign_id', $campaign->id)
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when(
                array_key_exists('has_website', $filters) && $filters['has_website'] !== null,
                function (Builder $q) use ($filters) {
                    $filters['has_website']
                        ? $q->whereNotNull('website_url')
                        : $q->whereNull('website_url');
                }
            )
            ->when($filters['min_score'] ?? null, function (Builder $q, $minScore) {
                $q->whereHas('latestAudit', fn (Builder $qq) => $qq->where('audit_score', '>=', $minScore));
            })
            ->when($filters['max_score'] ?? null, function (Builder $q, $maxScore) {
                $q->whereHas('latestAudit', fn (Builder $qq) => $qq->where('audit_score', '<=', $maxScore));
            })
            ->when($filters['province'] ?? null, fn (Builder $q, string $province) => $q->where('province', $province))
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where('company_name', 'like', "%{$search}%");
            });
    }

    public function updateStatus(Lead $lead, LeadStatus $status): Lead
    {
        $lead->update(['status' => $status->value]);

        return $lead->refresh();
    }

    public function addNote(Lead $lead, User $user, string $note): LeadNote
    {
        return $lead->notes()->create([
            'user_id' => $user->id,
            'note' => $note,
        ]);
    }

    public function streamCsv(Campaign $campaign, array $filters): StreamedResponse
    {
        $leads = $this->exportForCampaign($campaign, $filters);

        $filename = 'leads-'.$campaign->id.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($leads) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Thai text opens correctly in Excel.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Company Name', 'Phone', 'Website', 'Address', 'Province',
                'Business Type', 'Status', 'Audit Score', 'Website Issue',
                'Source', 'Discovered At',
            ]);

            foreach ($leads as $lead) {
                fputcsv($handle, [
                    $lead->company_name,
                    $lead->phone,
                    $lead->website_url,
                    $lead->address,
                    $lead->province,
                    $lead->business_type,
                    $lead->status->value,
                    $lead->latestAudit?->audit_score,
                    $lead->latestAudit?->issues ? $this->summarizeIssues($lead->latestAudit->issues) : null,
                    $lead->source,
                    $lead->discovered_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function summarizeIssues(array $issues): string
    {
        return collect($issues)->pluck('message_th')->implode('; ');
    }
}
