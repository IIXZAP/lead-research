<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LeadStatus;
use App\Http\Requests\Lead\UpdateLeadStatusRequest;
use App\Models\Campaign;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    public function __construct(private readonly LeadService $leads)
    {
    }

    /**
     * Leads Directory แบบรวมทุก Campaign (ui/leads.html) — ต่างจาก index()
     * ด้านล่างซึ่งเป็น Leads ของ Campaign เดียว (ui/campaign-detail.html tab "Leads")
     */
    public function all(Request $request): View
    {
        $this->authorize('viewAny', Lead::class);

        $filters = $this->filtersFromRequest($request);
        $perPage = in_array((int) $request->query('per_page'), [10, 20, 50], true) ? (int) $request->query('per_page') : 20;

        $leads = $this->leads->listAllFor($request->user(), $filters, $perPage);
        $summary = $this->leads->summaryFor($request->user());
        $filterOptions = $this->leads->filterOptionsFor($request->user());

        // แนบคะแนน "ความสมบูรณ์" ต่อ record ไว้ล่วงหน้า กันไม่ให้ view ต้อง resolve
        // service ซ้ำต่อแถว
        $leads->getCollection()->each(function (Lead $lead) {
            $lead->completeness = $this->leads->completenessScore($lead);
        });

        return view('leads.all', [
            'leads' => $leads,
            'filters' => $filters,
            'statuses' => LeadStatus::cases(),
            'summary' => $summary,
            'perPage' => $perPage,
            'provinceOptions' => $filterOptions['provinces'],
            'businessTypeOptions' => $filterOptions['business_types'],
        ]);
    }

    public function exportAll(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Lead::class);

        return $this->leads->streamCsvAll($request->user(), $this->filtersFromRequest($request));
    }

    public function index(Request $request, Campaign $campaign): View
    {
        $this->authorize('view', $campaign);

        $filters = $this->filtersFromRequest($request);
        $leads = $this->leads->listForCampaign($campaign, $filters);

        return view('leads.index', [
            'campaign' => $campaign,
            'leads' => $leads,
            'filters' => $filters,
            'statuses' => LeadStatus::cases(),
        ]);
    }

    public function show(Lead $lead): View
    {
        $this->authorize('view', $lead);

        $lead->load(['campaign', 'audits' => fn ($q) => $q->latest('audited_at'), 'notes.user', 'assignedUser']);

        return view('leads.show', ['lead' => $lead]);
    }

    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead): RedirectResponse
    {
        $this->leads->updateStatus($lead, LeadStatus::from($request->validated('status')));

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'อัปเดตสถานะแล้ว');
    }

    public function export(Request $request, Campaign $campaign): StreamedResponse
    {
        $this->authorize('export', $campaign);

        return $this->leads->streamCsv($campaign, $this->filtersFromRequest($request));
    }

    private function filtersFromRequest(Request $request): array
    {
        return array_filter([
            'status' => $request->query('status'),
            'has_website' => $this->parseYesNo($request->query('has_website')),
            'has_phone' => $this->parseYesNo($request->query('has_phone')),
            'min_score' => $request->query('min_score'),
            'max_score' => $request->query('max_score'),
            'province' => $request->query('province'),
            'business_type' => $request->query('business_type'),
            'search' => $request->query('search'),
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * ฟอร์มใน ui/leads.html ใช้ค่า "yes"/"no" ในทุก select ที่เป็น
     * boolean filter (has_website, has_phone) แทน 1/0 — รองรับทั้งสองแบบ
     * เผื่อ integration อื่นยังส่ง 1/0 มา (เช่นลิงก์ export จากหน้า campaign leads เดิม)
     */
    private function parseYesNo(?string $value): ?bool
    {
        return match ($value) {
            'yes', '1' => true,
            'no', '0' => false,
            default => null,
        };
    }
}
