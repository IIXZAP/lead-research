@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('campaigns.show', $campaign) }}" class="text-sm text-ink-muted hover:text-brand transition-colors">&larr; {{ $campaign->name }}</a>
            <h1 class="text-2xl font-display font-semibold mt-1">Leads</h1>
        </div>
        @can('export', $campaign)
            <a href="{{ route('leads.export', array_merge(['campaign' => $campaign->id], $filters)) }}" class="btn-primary">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0l-4-4m4 4l4-4M4 21h16"/></svg>
                Export CSV
            </a>
        @endcan
    </div>

    <form method="GET" action="{{ route('leads.index', $campaign) }}" class="card p-4 mb-6 grid grid-cols-2 md:grid-cols-6 gap-3">
        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="ค้นหาชื่อบริษัท" class="input col-span-2">

        <select name="status" class="input">
            <option value="">สถานะทั้งหมด</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>{{ $status->value }}</option>
            @endforeach
        </select>

        <select name="has_website" class="input">
            <option value="">มี/ไม่มีเว็บไซต์</option>
            <option value="1" @selected(($filters['has_website'] ?? null) === true)>มีเว็บไซต์</option>
            <option value="0" @selected(($filters['has_website'] ?? null) === false)>ไม่มีเว็บไซต์</option>
        </select>

        <input type="number" name="min_score" value="{{ $filters['min_score'] ?? '' }}" placeholder="คะแนนขั้นต่ำ" min="0" max="100" class="input">
        <input type="number" name="max_score" value="{{ $filters['max_score'] ?? '' }}" placeholder="คะแนนสูงสุด" min="0" max="100" class="input">

        <div class="col-span-2 md:col-span-6 flex gap-2 pt-1">
            <button type="submit" class="btn-primary">กรอง</button>
            <a href="{{ route('leads.index', $campaign) }}" class="btn-ghost">ล้างตัวกรอง</a>
        </div>
    </form>

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-brand-light/40 text-left text-ink-muted">
                <tr>
                    <th class="px-4 py-3 font-medium">Company</th>
                    <th class="px-4 py-3 font-medium">Phone</th>
                    <th class="px-4 py-3 font-medium">Website</th>
                    <th class="px-4 py-3 font-medium">Score</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($leads as $lead)
                    <tr class="transition-colors hover:bg-brand-light/30">
                        <td class="px-4 py-3 font-medium">{{ $lead->company_name }}</td>
                        <td class="px-4 py-3 font-mono text-ink-muted">{{ $lead->phone ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($lead->website_url)
                                <a href="{{ $lead->website_url }}" target="_blank" rel="noopener" class="text-brand hover:underline">เว็บไซต์</a>
                            @else
                                <span class="text-ink-muted">ไม่มี</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($lead->latestAudit?->audit_score !== null)
                                <span class="badge font-mono
                                    {{ $lead->latestAudit->audit_score < 40 ? 'bg-red-100 text-red-700' : ($lead->latestAudit->audit_score < 70 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                    {{ $lead->latestAudit->audit_score }}
                                </span>
                            @else
                                <span class="text-ink-muted">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-ink-muted">{{ $lead->status->value }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('leads.show', $lead) }}" class="nav-link">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-ink-muted">ไม่พบ lead ตามเงื่อนไขที่กรอง</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $leads->links() }}
    </div>
@endsection
