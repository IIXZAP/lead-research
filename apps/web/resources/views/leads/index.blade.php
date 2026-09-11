@extends('layouts.app')

@php($activePage = 'campaigns')
@php($pageTitle = 'Leads — '.$campaign->name)

@section('title', 'Leads')

@section('content')

    <a href="{{ route('campaigns.show', $campaign) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-indigo-600">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> กลับไปหน้า Campaign
    </a>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Leads</h1>
            <p class="text-sm text-slate-500 mt-0.5">
                รายชื่อบริษัทที่ค้นพบใน Campaign "{{ $campaign->name }}" · <span>{{ $leads->total() }}</span> รายการ
            </p>
        </div>
        @can('export', $campaign)
            <a href="{{ route('leads.export', array_merge(['campaign' => $campaign->id], $filters)) }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-lg px-3.5 py-2">
                <i data-lucide="download" class="w-4 h-4"></i> Export CSV
            </a>
        @endcan
    </div>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('leads.index', $campaign) }}"
          class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-3 flex flex-wrap items-center gap-2">
        <div class="flex items-center gap-2 flex-1 min-w-[200px]">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 ml-1"></i>
            <input name="search" type="text" value="{{ $filters['search'] ?? '' }}" placeholder="ค้นหาชื่อบริษัท, เบอร์โทร..."
                   class="flex-1 text-sm outline-none placeholder:text-slate-400" />
        </div>

        <select name="status" class="text-sm rounded-lg border border-slate-200 px-3 py-2 outline-none">
            <option value="">ทุกสถานะ</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" {{ ($filters['status'] ?? '') === $status->value ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                </option>
            @endforeach
        </select>

        <select name="has_website" class="text-sm rounded-lg border border-slate-200 px-3 py-2 outline-none">
            <option value="">ทุกเว็บไซต์</option>
            <option value="1" {{ ($filters['has_website'] ?? null) === true ? 'selected' : '' }}>มีเว็บไซต์</option>
            <option value="0" {{ array_key_exists('has_website', $filters) && $filters['has_website'] === false ? 'selected' : '' }}>ไม่มีเว็บไซต์</option>
        </select>

        <button type="submit" class="text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg px-4 py-2">กรอง</button>
    </form>

    <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">บริษัท</th>
                    <th class="px-4 py-3 font-medium">เบอร์โทร</th>
                    <th class="px-4 py-3 font-medium">เว็บไซต์</th>
                    <th class="px-4 py-3 font-medium">คะแนน</th>
                    <th class="px-4 py-3 font-medium">สถานะ</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($leads as $lead)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-800">{{ $lead->company_name }}</p>
                            <p class="text-xs text-slate-400">{{ $lead->province }} {{ $lead->district }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $lead->phone ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if ($lead->website_url)
                                <span class="inline-flex items-center gap-1 text-emerald-600"><i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> มี</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-slate-400"><i data-lucide="circle-slash" class="w-3.5 h-3.5"></i> ไม่มี</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-slate-700">{{ $lead->rating ?? '-' }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$lead->status->value" /></td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('leads.show', $lead) }}" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 font-medium">
                                ดูรายละเอียด <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-14 text-center text-slate-400">
                            <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2"></i>
                            ยังไม่พบ Lead ตามเงื่อนไขที่เลือก
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $leads->appends($filters)->links() }}</div>

@endsection
