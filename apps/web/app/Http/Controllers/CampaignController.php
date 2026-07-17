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
        return view('campaigns.create');
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $campaign = $this->campaigns->create($request->user(), $request->validated());

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('status', 'สร้างแคมเปญเรียบร้อยแล้ว');
    }

    public function show(Campaign $campaign): View
    {
        $campaign->load(['user', 'researchJobs' => fn ($query) => $query->latest()->limit(1)]);

        return view('campaigns.show', ['campaign' => $campaign]);
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
