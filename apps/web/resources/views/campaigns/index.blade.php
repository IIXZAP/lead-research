@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-display font-semibold">Campaigns</h1>
        @can('create', \App\Models\Campaign::class)
            <a href="{{ route('campaigns.create') }}" class="btn-primary">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Campaign
            </a>
        @endcan
    </div>

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-brand-light/40 text-left text-ink-muted">
                <tr>
                    <th class="px-4 py-3 font-medium">Campaign</th>
                    <th class="px-4 py-3 font-medium">Keyword</th>
                    <th class="px-4 py-3 font-medium">Location</th>
                    <th class="px-4 py-3 font-medium">Leads</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Created At</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($campaigns as $campaign)
                    <tr class="transition-colors hover:bg-brand-light/30">
                        <td class="px-4 py-3 font-medium">{{ $campaign->name }}</td>
                        <td class="px-4 py-3 text-ink-muted">{{ $campaign->business_keyword }}</td>
                        <td class="px-4 py-3 text-ink-muted">{{ $campaign->province }}</td>
                        <td class="px-4 py-3 font-mono">{{ $campaign->leads_count }}</td>
                        <td class="px-4 py-3">
                            @php($s = $campaign->status->value)
                            <span class="badge
                                {{ match(true) {
                                    in_array($s, ['completed']) => 'bg-emerald-100 text-emerald-700',
                                    in_array($s, ['processing','queued']) => 'bg-brand-light text-brand',
                                    in_array($s, ['failed']) => 'bg-red-100 text-red-700',
                                    in_array($s, ['partially_completed']) => 'bg-amber-100 text-amber-700',
                                    default => 'bg-gray-100 text-gray-600',
                                } }}">{{ $s }}</span>
                        </td>
                        <td class="px-4 py-3 text-ink-muted">{{ $campaign->created_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('campaigns.show', $campaign) }}" class="nav-link">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-ink-muted">ยังไม่มี campaign</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $campaigns->links() }}
    </div>
@endsection
