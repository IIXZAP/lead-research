<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\LeadStatus;
use App\Models\ApiUsageLog;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\ResearchJob;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $campaignIds = match ($user->account_type) {
            AccountType::Admin => Campaign::query()->pluck('id'),
            AccountType::Sales => Campaign::query()->where('user_id', $user->id)->pluck('id'),
            AccountType::Viewer => collect(),
        };

        $leadsQuery = Lead::query()->whereIn('campaign_id', $campaignIds);

        $stats = [
            'campaign_count' => $campaignIds->count(),
            'lead_count' => (clone $leadsQuery)->count(),
            'new_lead_count' => (clone $leadsQuery)->where('status', LeadStatus::New->value)->count(),
            'qualified_lead_count' => (clone $leadsQuery)->where('status', LeadStatus::Qualified->value)->count(),
            'leads_without_website_count' => (clone $leadsQuery)->whereNull('website_url')->count(),
            'high_severity_issue_count' => (clone $leadsQuery)
                ->whereHas('latestAudit', fn ($query) => $query->where('audit_score', '<', 40))
                ->count(),
            'credits_used' => ApiUsageLog::query()
                ->whereIn('campaign_id', $campaignIds)
                ->sum('credit_used'),
            'running_job_count' => ResearchJob::query()
                ->whereIn('campaign_id', $campaignIds)
                ->whereIn('status', ['pending', 'processing'])
                ->count(),
        ];

        return view('dashboard', ['stats' => $stats]);
    }
}
