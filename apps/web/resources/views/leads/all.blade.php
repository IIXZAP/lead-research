@extends('layouts.app')

@php($activePage = 'leads')
@php($pageTitle = 'Leads')
@php($activeFilterCount = collect($filters)->except(['search'])->filter(fn ($v) => $v !== null && $v !== '')->count())

@section('title', 'Leads')

@section('content')

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Leads</h1>
            <p class="text-sm text-slate-500 mt-0.5">รายชื่อบริษัทและนิติบุคคลที่ค้นพบจากทุก Campaign · <span>{{ $leads->total() }}</span> รายการทั้งหมด</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('leads.export-all', $filters) }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-lg px-3 py-2">
                <i data-lucide="download" class="w-4 h-4"></i> <span class="hidden sm:inline">Export CSV</span>
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4">
            <span class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center"><i data-lucide="users" class="w-4.5 h-4.5"></i></span>
            <p class="text-2xl font-bold text-slate-800 mt-3">{{ $summary['total'] }}</p>
            <p class="text-sm text-slate-500 mt-0.5">Lead ทั้งหมด</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4">
            <span class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center"><i data-lucide="globe" class="w-4.5 h-4.5"></i></span>
            <p class="text-2xl font-bold text-slate-800 mt-3">{{ $summary['with_website'] }}</p>
            <p class="text-sm text-slate-500 mt-0.5">มี Website</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4">
            <span class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center"><i data-lucide="phone" class="w-4.5 h-4.5"></i></span>
            <p class="text-2xl font-bold text-slate-800 mt-3">{{ $summary['with_phone'] }}</p>
            <p class="text-sm text-slate-500 mt-0.5">มีเบอร์โทร</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4">
            <span class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center"><i data-lucide="gauge" class="w-4.5 h-4.5"></i></span>
            <p class="text-2xl font-bold text-slate-800 mt-3">{{ $summary['avg_completeness'] }}%</p>
            <p class="text-sm text-slate-500 mt-0.5">ความสมบูรณ์เฉลี่ย</p>
        </div>
    </div>

    {{-- Search & Filters --}}
    <form method="GET" action="{{ route('leads.all') }}" id="leads-filter-form" class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4 space-y-3">
        <div class="flex flex-col lg:flex-row gap-3">
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input name="search" value="{{ $filters['search'] ?? '' }}" type="text" placeholder="ค้นหาชื่อบริษัท (ไทย/อังกฤษ)..."
                       class="w-full pl-9 pr-3 py-2.5 text-sm rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none transition" />
            </div>
            <button type="button" id="btn-toggle-filters"
                    class="inline-flex items-center justify-center gap-1.5 text-sm font-medium text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-lg px-3.5 py-2">
                <i data-lucide="sliders-horizontal" class="w-4 h-4"></i> ตัวกรอง
                @if ($activeFilterCount > 0)
                    <span class="ml-1 bg-indigo-600 text-white text-[10px] rounded-full w-4 h-4 flex items-center justify-center">{{ $activeFilterCount }}</span>
                @endif
            </button>
            <button type="submit"
                    class="inline-flex items-center justify-center text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg px-4 py-2.5">
                ค้นหา
            </button>
        </div>

        <div id="filters-panel" class="{{ $activeFilterCount > 0 ? '' : 'hidden' }} grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 pt-3 border-t border-slate-100">
            <select name="province" class="filter-input text-sm rounded-lg border border-slate-200 px-3 py-2 outline-none">
                <option value="">จังหวัดทั้งหมด</option>
                @foreach ($provinceOptions as $province)
                    <option value="{{ $province }}" {{ ($filters['province'] ?? '') === $province ? 'selected' : '' }}>{{ $province }}</option>
                @endforeach
            </select>

            <select name="business_type" class="filter-input text-sm rounded-lg border border-slate-200 px-3 py-2 outline-none">
                <option value="">ประเภทธุรกิจทั้งหมด</option>
                @foreach ($businessTypeOptions as $type)
                    <option value="{{ $type }}" {{ ($filters['business_type'] ?? '') === $type ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>

            <select name="status" class="filter-input text-sm rounded-lg border border-slate-200 px-3 py-2 outline-none">
                <option value="">สถานะ Lead ทั้งหมด</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" {{ ($filters['status'] ?? '') === $status->value ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                    </option>
                @endforeach
            </select>

            <select name="has_website" class="filter-input text-sm rounded-lg border border-slate-200 px-3 py-2 outline-none">
                <option value="">Website ทั้งหมด</option>
                <option value="yes" {{ ($filters['has_website'] ?? null) === true ? 'selected' : '' }}>มี Website</option>
                <option value="no" {{ array_key_exists('has_website', $filters) && $filters['has_website'] === false ? 'selected' : '' }}>ไม่มี Website</option>
            </select>

            <select name="has_phone" class="filter-input text-sm rounded-lg border border-slate-200 px-3 py-2 outline-none">
                <option value="">เบอร์โทรทั้งหมด</option>
                <option value="yes" {{ ($filters['has_phone'] ?? null) === true ? 'selected' : '' }}>มีเบอร์โทร</option>
                <option value="no" {{ array_key_exists('has_phone', $filters) && $filters['has_phone'] === false ? 'selected' : '' }}>ไม่มีเบอร์โทร</option>
            </select>
        </div>

        <div id="filters-reset-row" class="{{ $activeFilterCount > 0 ? 'flex' : 'hidden' }} justify-end">
            <a href="{{ route('leads.all') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">รีเซ็ตตัวกรองทั้งหมด</a>
        </div>
    </form>

    {{-- Table (desktop) --}}
    <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm overflow-hidden hidden md:block">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 sticky top-0 z-10">
                    <tr class="text-left text-slate-500 text-xs uppercase tracking-wide">
                        <th class="px-4 py-3">Company</th>
                        <th class="px-4 py-3">Website</th>
                        <th class="px-4 py-3">Phone</th>
                        {{-- <th class="px-4 py-3">จังหวัด</th> --}}
                        <th class="px-4 py-3">ประเภทธุรกิจ</th>
                        <th class="px-4 py-3">สถานะ Lead</th>
                        <th class="px-4 py-3">ความสมบูรณ์</th>
                        <th class="px-4 py-3">วันที่ค้นพบ</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($leads as $lead)
                        @php($completeness = $lead->completeness)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-semibold shrink-0">
                                        {{ mb_substr($lead->company_name, 0, 2) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-medium text-slate-800 truncate">{{ Str::limit($lead->company_name, 30) }}</p>
                                        <p class="text-xs text-slate-400 truncate">{{ $lead->campaign->name ?? '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if ($lead->website_url)
                                    <a href="{{ $lead->website_url }}" target="_blank" class="text-indigo-600 hover:text-indigo-700 inline-flex items-center gap-1">
                                        {{ Str::limit($lead->normalized_domain ?? $lead->website_url, 24) }} <i data-lucide="external-link" class="w-3 h-3"></i>
                                    </a>
                                @else
                                    <span class="text-slate-400">ไม่พบเว็บไซต์</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $lead->phone ?? '-' }}</td>
                            {{-- <td class="px-4 py-3 text-slate-500">{{ $lead->province ?? '-' }}</td> --}}
                            <td class="px-4 py-3 text-slate-500">{{ $lead->business_type ?? '-' }}</td>
                            <td class="px-4 py-3"><x-status-badge :status="$lead->status->value" /></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center justify-center w-9 h-9 rounded-full text-xs font-semibold ring-2
                                    {{ $completeness >= 80 ? 'text-emerald-600 ring-emerald-200 bg-emerald-50' : ($completeness >= 50 ? 'text-amber-600 ring-amber-200 bg-amber-50' : 'text-rose-600 ring-rose-200 bg-rose-50') }}">
                                    {{ $completeness }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ optional($lead->discovered_at)->format('d M Y') ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('leads.show', $lead) }}" class="text-slate-400 hover:text-indigo-600">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-14 text-center text-slate-400">
                                <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2"></i>
                                ยังไม่พบ Lead ตามเงื่อนไขที่เลือก
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Card list (mobile) --}}
    <div class="md:hidden space-y-3">
        @forelse ($leads as $lead)
            @php($completeness = $lead->completeness)
            <a href="{{ route('leads.show', $lead) }}" class="block bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4">
                <div class="flex items-start gap-3">
                    <span class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-semibold shrink-0">
                        {{ mb_substr($lead->company_name, 0, 2) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-medium text-slate-800 truncate">{{ Str::limit($lead->company_name, 28) }}</p>
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-semibold ring-2 shrink-0
                                {{ $completeness >= 80 ? 'text-emerald-600 ring-emerald-200 bg-emerald-50' : ($completeness >= 50 ? 'text-amber-600 ring-amber-200 bg-amber-50' : 'text-rose-600 ring-rose-200 bg-rose-50') }}">
                                {{ $completeness }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $lead->campaign->name ?? '-' }} · {{ $lead->province }}</p>
                        <div class="flex items-center gap-2 mt-2 flex-wrap">
                            <x-status-badge :status="$lead->status->value" />
                            @if ($lead->website_url)
                                <span class="text-xs text-emerald-600 inline-flex items-center gap-1"><i data-lucide="globe" class="w-3 h-3"></i> Website</span>
                            @endif
                            @if ($lead->phone)
                                <span class="text-xs text-slate-500 inline-flex items-center gap-1"><i data-lucide="phone" class="w-3 h-3"></i> {{ $lead->phone }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-10 text-center text-slate-400">
                <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2"></i>
                ยังไม่พบ Lead ตามเงื่อนไขที่เลือก
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4">
        <form method="GET" action="{{ route('leads.all') }}" class="flex items-center gap-2 text-sm text-slate-500">
            @foreach (collect($filters)->except('per_page') as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}" />
            @endforeach
            <span>แสดง</span>
            <select name="per_page" onchange="this.form.submit()" class="text-sm rounded-lg border border-slate-200 px-2 py-1.5 outline-none">
                <option value="10" {{ $perPage === 10 ? 'selected' : '' }}>10</option>
                <option value="20" {{ $perPage === 20 ? 'selected' : '' }}>20</option>
                <option value="50" {{ $perPage === 50 ? 'selected' : '' }}>50</option>
            </select>
            <span>รายการต่อหน้า</span>
        </form>

        <p class="text-sm text-slate-500">
            {{ $leads->firstItem() ?? 0 }}–{{ $leads->lastItem() ?? 0 }} จาก {{ $leads->total() }} รายการ
        </p>

        <div>{{ $leads->links() }}</div>
    </div>

@endsection

@push('scripts')
<script>
    document.getElementById('btn-toggle-filters')?.addEventListener('click', () => {
        document.getElementById('filters-panel').classList.toggle('hidden');
        document.getElementById('filters-reset-row').classList.toggle('hidden');
    });

    // ทุก select ในแผงตัวกรอง submit ฟอร์มทันทีที่เปลี่ยนค่า เหมือนพฤติกรรมเดิมใน ui/assets/js/leads.js
    document.querySelectorAll('#filters-panel select').forEach((el) => {
        el.addEventListener('change', () => document.getElementById('leads-filter-form').submit());
    });
</script>
@endpush
