@extends('layouts.app')

@php($activePage = 'campaigns')
@php($pageTitle = 'Campaigns')

@section('title', 'Campaigns')

@section('content')

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Campaigns</h1>
            <p class="text-sm text-slate-500 mt-0.5">
                จัดการแคมเปญค้นหา Lead ลูกค้าธุรกิจ · <span>{{ $campaigns->total() }}</span> รายการทั้งหมด
            </p>
        </div>
        <div class="flex items-center gap-2">
            @can('create', \App\Models\Campaign::class)
                <a href="{{ route('campaigns.create') }}"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg px-3.5 py-2">
                    <i data-lucide="plus" class="w-4 h-4"></i> สร้าง Campaign
                </a>
            @endcan
        </div>
    </div>

    {{-- <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4 hover:shadow-md transition-shadow">
            <div class="w-9 h-9 rounded-lg ${colorMap[c.color]} flex items-center justify-center mb-3">
                <i data-lucide="${c.icon}" class="w-5 h-5"></i>
            </div>
            <p class="text-2xl font-bold text-slate-800"></p>
            <div class="flex items-center justify-between mt-1">
                <p class="text-xs text-slate-500"></p>
                <span class="text-xs font-medium  flex items-center gap-0.5
                    shrink-0">
                    <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                </span>
            </div>
        </div>
    </div> --}}

    {{-- ค้นหาแบบ client-side บนแถวที่โหลดมาในหน้านี้ --}}
    <div class="bg-white rounded-xl ring-1 ring-slate-100 shadow-sm p-3 flex items-center gap-2">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 ml-1"></i>
        <input id="campaign-search" type="text" placeholder="ค้นหาชื่อ Campaign, คำค้นหา หรือจังหวัด..."
            class="flex-1 text-sm outline-none focus:outline-none placeholder:text-slate-400" />
    </div>

    <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm overflow-hidden">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3 w-10">
                        <input type="checkbox" id="select-all-checkbox"
                            class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                    </th>
                    <th class="px-4 py-3 font-medium uppercase">Campaign ID</th>
                    <th class="px-4 py-3 font-medium uppercase">ชื่อ Campaign</th>
                    <th class="px-4 py-3 font-medium">พื้นที่</th>
                    <th class="px-4 py-3 font-medium">รัศมี</th>
                    <th class="px-4 py-3 font-medium">ประเภทธุรกิจ</th>
                    <th class="px-4 py-3 font-medium">สถานะ</th>
                    <th class="px-4 py-3 font-medium">วันที่สร้าง</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody id="campaign-rows" class="divide-y divide-slate-50">
                @forelse ($campaigns as $campaign)
                    <tr class="campaign-row hover:bg-slate-50 transition-colors"
                        data-search="{{ mb_strtolower($campaign->name . ' ' . $campaign->business_keyword . ' ' . $campaign->province) }}">
                        <td class="px-4 py-3">
                            <input type="checkbox"
                                class="row-checkbox w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                data-id="{{ $campaign->id }}" />
                        </td>
                        <td class="px-4 py-3 font-mono text-slate-500">CMP-2026-{{ $campaign->id }}</td>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $campaign->name }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $campaign->province ?? ($campaign->location_text ?? '—') }}
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $campaign->radius_km ?? '—' }} กม.</td>
                        <td class="px-4 py-3 text-slate-500">
                            {{ $campaign->business_category ?? $campaign->business_keyword }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$campaign->status->value" /></td>
                        <td class="px-4 py-3 text-slate-500">{{ $campaign->created_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('campaigns.show', $campaign) }}"
                                class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 font-medium">
                                ดูข้อมูล <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-14 text-center text-slate-400">
                            <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2"></i>
                            ยังไม่มี Campaign
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div
        class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4">
        <div class="flex items-center gap-2 text-sm text-slate-500">
            <span>แสดง</span>
            <select id="page-size-select" class="filter-input !py-1.5">
                <option value="10" selected>10</option>
                <option value="20">20</option>
                <option value="50">50</option>
            </select>
            <span>รายการต่อหน้า</span>
        </div>
        <p id="pagination-info" class="text-sm text-slate-500"></p>
        <div id="pagination-controls" class="flex items-center gap-1"></div>
    </div>

    <div>{{ $campaigns->links() }}</div>

@endsection

@push('scripts')
    <script>
        document.getElementById('campaign-search')?.addEventListener('input', (e) => {
            const q = e.target.value.trim().toLowerCase();
            document.querySelectorAll('.campaign-row').forEach((row) => {
                row.classList.toggle('hidden', q !== '' && !row.dataset.search.includes(q));
            });
        });
    </script>
@endpush
