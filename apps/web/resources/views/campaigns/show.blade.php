@extends('layouts.app')

@section('content')
    @php($isRunning = in_array($campaign->status->value, ['queued', 'processing']))

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-display font-semibold">{{ $campaign->name }}</h1>
            <p class="text-sm text-ink-muted mt-1">
                <span class="badge
                    {{ match(true) {
                        $campaign->status->value === 'completed' => 'bg-emerald-100 text-emerald-700',
                        in_array($campaign->status->value, ['processing','queued']) => 'bg-brand-light text-brand',
                        $campaign->status->value === 'failed' => 'bg-red-100 text-red-700',
                        $campaign->status->value === 'partially_completed' => 'bg-amber-100 text-amber-700',
                        default => 'bg-gray-100 text-gray-600',
                    } }}">{{ $campaign->status->value }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('leads.index', $campaign) }}" class="btn-secondary">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                View Leads
            </a>
            @can('startJob', $campaign)
                @if (in_array($campaign->status->value, ['draft', 'failed', 'cancelled', 'completed', 'partially_completed']))
                    <form method="POST" action="{{ route('campaigns.start', $campaign) }}">
                        @csrf
                        <button type="submit" class="btn-primary">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            Start
                        </button>
                    </form>
                @endif
            @endcan
            @can('cancelJob', $campaign)
                @if ($isRunning)
                    <form method="POST" action="{{ route('campaigns.cancel', $campaign) }}">
                        @csrf
                        <button type="submit" class="btn-secondary text-amber-700 border-amber-200 hover:bg-amber-50">Cancel</button>
                    </form>
                @endif
            @endcan
            @can('update', $campaign)
                <a href="{{ route('campaigns.edit', $campaign) }}" class="btn-ghost">Edit</a>
            @endcan
            @can('delete', $campaign)
                <form method="POST" action="{{ route('campaigns.destroy', $campaign) }}" onsubmit="return confirm('ลบแคมเปญนี้?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">Delete</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="card p-6">
            <h2 class="text-sm font-semibold text-ink-muted mb-4">Search criteria</h2>
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between"><dt class="text-ink-muted">Keyword</dt><dd class="font-medium">{{ $campaign->business_keyword }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Province</dt><dd class="font-medium">{{ $campaign->province ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Radius</dt><dd class="font-mono">{{ $campaign->radius_km }} km</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Maximum leads</dt><dd class="font-mono">{{ $campaign->maximum_leads }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Owner</dt><dd class="font-medium">{{ $campaign->user->name }}</dd></div>
            </dl>
        </div>

        <div class="card p-6">
            <h2 class="text-sm font-semibold text-ink-muted mb-4">Job progress</h2>
            @if ($campaign->researchJobs->isNotEmpty())
                @php($job = $campaign->researchJobs->first())
                <dl class="space-y-2.5 text-sm mb-4">
                    <div class="flex justify-between"><dt class="text-ink-muted">Stage</dt><dd class="font-medium">{{ $job->current_stage ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-muted">Processed</dt><dd class="font-mono">{{ $job->processed_items }} / {{ $job->total_items }}</dd></div>
                </dl>

                <div class="flex items-center justify-between text-xs text-ink-muted mb-1.5">
                    <span>Progress</span>
                    <span class="font-mono">{{ $job->progress_percent }}%</span>
                </div>
                @if ($isRunning)
                    <div class="scan-track h-2">
                        <div class="scan-sweep h-full"></div>
                        <div class="h-full bg-brand rounded-full transition-all duration-500" style="width: {{ $job->progress_percent }}%"></div>
                    </div>
                @else
                    <div class="h-2 rounded-full bg-brand-light overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500 {{ $campaign->status->value === 'failed' ? 'bg-red-500' : 'bg-brand' }}" style="width: {{ $job->progress_percent }}%"></div>
                    </div>
                @endif
            @else
                <p class="text-sm text-ink-muted">ยังไม่มี research job ที่รันไว้</p>
            @endif
        </div>
    </div>
@endsection
