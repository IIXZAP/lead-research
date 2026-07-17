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
        $hasWebsite = $request->query('has_website');

        return array_filter([
            'status' => $request->query('status'),
            'has_website' => $hasWebsite === null || $hasWebsite === '' ? null : (bool) (int) $hasWebsite,
            'min_score' => $request->query('min_score'),
            'max_score' => $request->query('max_score'),
            'province' => $request->query('province'),
            'search' => $request->query('search'),
        ], fn ($value) => $value !== null && $value !== '');
    }
}
