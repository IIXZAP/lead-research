@extends('layouts.app')

@section('content')
    @php($latestAudit = $lead->audits->first())

    <a href="{{ route('leads.index', $lead->campaign) }}" class="text-sm text-ink-muted hover:text-brand transition-colors">&larr; Leads</a>

    <div class="flex items-center justify-between mt-2 mb-6">
        <h1 class="text-2xl font-display font-semibold">{{ $lead->company_name }}</h1>

        @can('updateStatus', $lead)
            <form method="POST" action="{{ route('leads.update-status', $lead) }}" class="flex items-center gap-2">
                @csrf
                @method('PATCH')
                <select name="status" class="input !w-auto">
                    @foreach (\App\Enums\LeadStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected($lead->status === $status)>{{ $status->value }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary">Update</button>
            </form>
        @endcan
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="card p-6">
            <h2 class="text-sm font-semibold text-ink-muted mb-4">Contact info</h2>
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between"><dt class="text-ink-muted">Phone</dt><dd class="font-mono">{{ $lead->phone ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Website</dt>
                    <dd>@if($lead->website_url)<a href="{{ $lead->website_url }}" target="_blank" rel="noopener" class="text-brand hover:underline">{{ $lead->website_url }}</a>@else — @endif</dd>
                </div>
                <div class="flex justify-between"><dt class="text-ink-muted">Address</dt><dd class="text-right">{{ $lead->address ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Province</dt><dd>{{ $lead->province ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Business type</dt><dd>{{ $lead->business_type ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Source</dt><dd class="font-mono text-xs">{{ $lead->source ?? '—' }}</dd></div>
            </dl>
        </div>

        <div class="card p-6">
            <h2 class="text-sm font-semibold text-ink-muted mb-4">Website audit</h2>
            @if ($latestAudit)
                <div class="flex items-center gap-3 mb-4">
                    <span class="badge font-mono text-base px-3 py-1
                        {{ ($latestAudit->audit_score ?? 0) < 40 ? 'bg-red-100 text-red-700' : (($latestAudit->audit_score ?? 0) < 70 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                        {{ $latestAudit->audit_score ?? '—' }}
                    </span>
                    @if ($latestAudit->confidence_score !== null)
                        <span class="text-xs text-ink-muted">confidence {{ number_format($latestAudit->confidence_score * 100) }}%</span>
                    @endif
                </div>
                @if (!empty($latestAudit->issues))
                    <ul class="space-y-2.5 text-sm">
                        @foreach ($latestAudit->issues as $issue)
                            <li class="flex items-start gap-2">
                                <span class="badge mt-0.5
                                    {{ in_array($issue['severity'], ['critical','high']) ? 'bg-red-100 text-red-700' : ($issue['severity'] === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-ink-muted') }}">
                                    {{ $issue['severity'] }}
                                </span>
                                <span>{{ $issue['message_th'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-ink-muted">ยังไม่พบปัญหาที่ชัดเจนจากการตรวจสอบเบื้องต้น</p>
                @endif
            @else
                <p class="text-sm text-ink-muted">ยังไม่มีข้อมูลการตรวจสอบเว็บไซต์</p>
            @endif
        </div>
    </div>

    <div class="card p-6 mt-6">
        <h2 class="text-sm font-semibold text-ink-muted mb-4">Notes</h2>

        <ul class="space-y-3 mb-4">
            @forelse ($lead->notes as $note)
                <li class="text-sm border-b border-line pb-3 last:border-0">
                    <p>{{ $note->note }}</p>
                    <p class="text-xs text-ink-muted mt-1">{{ $note->user->name }} · {{ $note->created_at->format('Y-m-d H:i') }}</p>
                </li>
            @empty
                <li class="text-sm text-ink-muted">ยังไม่มีโน้ต</li>
            @endforelse
        </ul>

        @can('addNote', $lead)
            <form method="POST" action="{{ route('leads.notes.store', $lead) }}" class="flex gap-2">
                @csrf
                <input type="text" name="note" required maxlength="2000" placeholder="เพิ่มโน้ต..." class="input flex-1">
                <button type="submit" class="btn-primary">Add</button>
            </form>
        @endcan
    </div>
@endsection
