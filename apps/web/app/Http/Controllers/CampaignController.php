<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function __construct(private readonly CampaignService $campaigns)
    {
        $this->authorizeResource(Campaign::class, 'campaign');
    }

    public function index(Request $request): View
    {
        $campaigns = $this->campaigns->listFor($request->user());

        return view('campaigns.index', ['campaigns' => $campaigns]);
    }

    public function create(): View
    {
        return view('campaigns.create', [
            'breadcrumbs' => [
                ['label' => 'Campaigns', 'url' => route('campaigns.index')],
                ['label' => 'สร้าง Campaign'],
            ],
        ]);
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $campaign = $this->campaigns->create($request->user(), $request->validated());

        if ($request->boolean('start_immediately') && $request->user()->can('startJob', $campaign)) {
            $this->campaigns->start($campaign);

            return redirect()
                ->route('campaigns.show', $campaign)
                ->with('status', 'สร้างแคมเปญและเริ่มค้นหาแล้ว ระบบจะทยอยแสดงผลลัพธ์ที่นี่');
        }

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('status', 'สร้างแคมเปญเรียบร้อยแล้ว (บันทึกเป็น Draft)');
    }

    public function show(Request $request, Campaign $campaign): View
    {
        // โหลด researchJobs ทั้งหมด (ไม่ limit 1 แบบเดิม) เพื่อใช้กับ Tab "Activity Logs"
        // ของหน้า campaign-detail แบบเต็ม (Overview/Leads/Search Criteria/Activity Logs)
        $campaign->load(['user', 'researchJobs' => fn($query) => $query->latest()]);

        $leadsQuery = $campaign->leads();

        $leadStats = [
            'total' => (clone $leadsQuery)->count(),
            'with_website' => (clone $leadsQuery)->whereNotNull('website_url')->count(),
            'with_phone' => (clone $leadsQuery)->whereNotNull('phone')->count(),
            'high_severity_issue_count' => (clone $leadsQuery)
                ->whereHas('latestAudit', fn($q) => $q->where('audit_score', '<', 40))
                ->count(),
        ];

        // Preview เฉพาะ 10 รายการล่าสุดสำหรับ Tab "Leads" ภายในหน้านี้
        // ดูรายการทั้งหมดพร้อม filter/export ที่หน้า leads.index แยกต่างหาก
        $leadsPreview = (clone $leadsQuery)->latest('discovered_at')->limit(10)->get();

        return view('campaigns.show', [
            'campaign' => $campaign,
            'leadStats' => $leadStats,
            'leadsPreview' => $leadsPreview,
        ]);
    }

    public function edit(Campaign $campaign): View
    {
        return view('campaigns.edit', ['campaign' => $campaign]);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->campaigns->update($campaign, $request->validated());

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('status', 'บันทึกการแก้ไขเรียบร้อยแล้ว');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $this->campaigns->delete($campaign);

        return redirect()
            ->route('campaigns.index')
            ->with('status', 'ลบแคมเปญเรียบร้อยแล้ว');
    }

    public function start(Campaign $campaign): RedirectResponse
    {
        $this->authorize('startJob', $campaign);

        $this->campaigns->start($campaign);

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('status', 'เริ่มค้นหาแล้ว ระบบจะทยอยแสดงผลลัพธ์ที่นี่');
    }

    public function cancel(Campaign $campaign): RedirectResponse
    {
        $this->authorize('cancelJob', $campaign);

        $this->campaigns->cancel($campaign);

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('status', 'ยกเลิกแคมเปญแล้ว');
    }
}
