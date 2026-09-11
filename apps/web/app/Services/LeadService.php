<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
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
        return $this->applyFilters(Lead::query()->where('campaign_id', $campaign->id), $filters)
            ->with('latestAudit')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function exportForCampaign(Campaign $campaign, array $filters): Collection
    {
        return $this->applyFilters(Lead::query()->where('campaign_id', $campaign->id), $filters)
            ->with('latestAudit')
            ->latest()
            ->get();
    }

    /**
     * หน้า Leads Directory แบบรวมทุก Campaign (ui/leads.html) — scope ตาม role
     * เดียวกับที่ DashboardController/CampaignService ใช้: Admin เห็นทั้งหมด,
     * Sales เห็นเฉพาะ Campaign ของตัวเอง, Viewer ยังไม่เปิดสิทธิ์ (รอฟีเจอร์
     * "granted campaigns" ตาม TODO ที่เหลือในระบบ)
     */
    public function listAllFor(User $user, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->applyFilters(Lead::query()->whereIn('campaign_id', $this->campaignIdsFor($user)), $filters)
            ->with(['latestAudit', 'campaign'])
            ->latest('discovered_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function summaryFor(User $user): array
    {
        $base = Lead::query()->whereIn('campaign_id', $this->campaignIdsFor($user));

        $total = (clone $base)->count();
        $withWebsite = (clone $base)->whereNotNull('website_url')->count();
        $withPhone = (clone $base)->whereNotNull('phone')->count();

        // "ความสมบูรณ์" คำนวณจากสัดส่วน field หลักที่กรอกครบ (phone/website/address/rating)
        // ไม่ใช่ตัวเลขที่ปั้นขึ้นมา แต่คำนวณจากข้อมูลจริงในแต่ละ record
        $avgCompleteness = $total > 0
            ? (int) round(
                (clone $base)->get()->avg(fn (Lead $lead) => $this->completenessScore($lead))
            )
            : 0;

        return [
            'total' => $total,
            'with_website' => $withWebsite,
            'with_phone' => $withPhone,
            'avg_completeness' => $avgCompleteness,
        ];
    }

    public function completenessScore(Lead $lead): int
    {
        $fields = [$lead->phone, $lead->website_url, $lead->address, $lead->rating];
        $filled = count(array_filter($fields, fn ($v) => $v !== null && $v !== ''));

        return (int) round(($filled / count($fields)) * 100);
    }

    public function streamCsvAll(User $user, array $filters): StreamedResponse
    {
        $leads = $this->applyFilters(Lead::query()->whereIn('campaign_id', $this->campaignIdsFor($user)), $filters)
            ->with(['latestAudit', 'campaign'])
            ->latest('discovered_at')
            ->get();

        return $this->streamLeadsAsCsv($leads, 'leads-all-'.now()->format('Ymd-His').'.csv', includeCampaign: true);
    }

    private function campaignIdsFor(User $user): Collection
    {
        return match ($user->account_type) {
            AccountType::Admin => Campaign::query()->pluck('id'),
            AccountType::Sales => Campaign::query()->where('user_id', $user->id)->pluck('id'),
            AccountType::Viewer => collect(),
        };
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
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
            ->when(
                array_key_exists('has_phone', $filters) && $filters['has_phone'] !== null,
                function (Builder $q) use ($filters) {
                    $filters['has_phone']
                        ? $q->whereNotNull('phone')
                        : $q->whereNull('phone');
                }
            )
            ->when($filters['province'] ?? null, fn (Builder $q, string $province) => $q->where('province', $province))
            ->when($filters['business_type'] ?? null, fn (Builder $q, string $type) => $q->where('business_type', $type))
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where('company_name', 'like', "%{$search}%");
            });
    }

    /**
     * ตัวเลือกสำหรับ dropdown จังหวัด/ประเภทธุรกิจในหน้า Leads Directory
     * (ui/leads.html สร้าง option เหล่านี้จาก mock data ที่โหลดมาทั้งหมด —
     * ในระบบจริงใช้ distinct() บนขอบเขต Lead ที่ user เห็นได้แทน)
     */
    public function filterOptionsFor(User $user): array
    {
        $base = Lead::query()->whereIn('campaign_id', $this->campaignIdsFor($user));

        return [
            'provinces' => (clone $base)->whereNotNull('province')->distinct()->orderBy('province')->pluck('province'),
            'business_types' => (clone $base)->whereNotNull('business_type')->distinct()->orderBy('business_type')->pluck('business_type'),
        ];
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

        return $this->streamLeadsAsCsv($leads, 'leads-'.$campaign->id.'-'.now()->format('Ymd-His').'.csv', includeCampaign: false);
    }

    private function streamLeadsAsCsv(Collection $leads, string $filename, bool $includeCampaign): StreamedResponse
    {
        return response()->streamDownload(function () use ($leads, $includeCampaign) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Thai text opens correctly in Excel.
            fwrite($handle, "\xEF\xBB\xBF");

            $headers = $includeCampaign ? ['Campaign'] : [];
            array_push($headers, ...[
                'Company Name', 'Phone', 'Website', 'Address', 'Province',
                'Business Type', 'Status', 'Audit Score', 'Website Issue',
                'Source', 'Discovered At',
            ]);
            fputcsv($handle, $headers);

            foreach ($leads as $lead) {
                $row = $includeCampaign ? [$lead->campaign->name ?? '-'] : [];
                array_push($row, ...[
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
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function summarizeIssues(array $issues): string
    {
        return collect($issues)->pluck('message_th')->implode('; ');
    }
}
