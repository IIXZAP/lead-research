@extends('layouts.app')

@php($activePage = 'campaigns')
@php($pageTitle = $campaign->name)
@php($isRunning = in_array($campaign->status->value, ['queued', 'processing']))
@php($latestJob = $campaign->researchJobs->first())

@section('title', $campaign->name)

@section('content')

    <a href="{{ route('campaigns.index') }}"
        class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-indigo-600">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> กลับไปหน้า Campaigns
    </a>

    {{-- Header card --}}
    <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-800">{{ $campaign->name }}</h1>
                    <x-status-badge :status="$campaign->status->value" />
                </div>
                <p class="text-sm text-slate-500 mt-1">
                    {{ $campaign->business_keyword }} · {{ $campaign->province }}
                    @if ($campaign->district)
                        / {{ $campaign->district }}
                    @endif
                </p>
                <p class="text-xs text-slate-400 mt-1">
                    #{{ $campaign->id }} · สร้างโดย {{ $campaign->user->name ?? '-' }} ·
                    {{ $campaign->created_at->format('Y-m-d H:i') }}
                </p>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 text-sm">
                    <div>
                        <p class="text-xs text-slate-400">Lead ที่พบ</p>
                        <p class="font-medium text-slate-700 mt-0.5">{{ $leadStats['total'] }} /
                            {{ $campaign->maximum_leads }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">มีเว็บไซต์</p>
                        <p class="font-medium text-slate-700 mt-0.5">{{ $leadStats['with_website'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">มีเบอร์โทร</p>
                        <p class="font-medium text-slate-700 mt-0.5">{{ $leadStats['with_phone'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">ปัญหาเว็บระดับสูง</p>
                        <p class="font-medium text-rose-600 mt-0.5">{{ $leadStats['high_severity_issue_count'] }}</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap shrink-0">
                @can('startJob', $campaign)
                    @if (in_array($campaign->status->value, ['draft', 'failed', 'cancelled', 'completed', 'partially_completed']))
                        <form method="POST" action="{{ route('campaigns.start', $campaign) }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg px-3.5 py-2">
                                <i data-lucide="play" class="w-4 h-4"></i> <span class="hidden sm:inline">เริ่มค้นหา</span>
                            </button>
                        </form>
                    @endif
                @endcan

                @can('cancelJob', $campaign)
                    @if ($isRunning)
                        <form method="POST" action="{{ route('campaigns.cancel', $campaign) }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center gap-1.5 text-sm font-medium text-amber-700 border border-amber-200 hover:bg-amber-50 rounded-lg px-3.5 py-2">
                                <i data-lucide="pause" class="w-4 h-4"></i> <span class="hidden sm:inline">หยุดชั่วคราว</span>
                            </button>
                        </form>
                    @endif
                @endcan

                @can('update', $campaign)
                    <a href="{{ route('campaigns.edit', $campaign) }}"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-lg px-3.5 py-2">
                        <i data-lucide="pencil" class="w-4 h-4"></i> <span class="hidden sm:inline">แก้ไข</span>
                    </a>
                @endcan

                @can('delete', $campaign)
                    <form method="POST" action="{{ route('campaigns.destroy', $campaign) }}"
                        onsubmit="return confirm('ลบแคมเปญนี้?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-rose-600 hover:bg-rose-50 rounded-lg px-3.5 py-2">
                            <i data-lucide="trash-2" class="w-4 h-4"></i> <span class="hidden sm:inline">ลบ</span>
                        </button>
                    </form>
                @endcan
            </div>
        </div>

        {{-- Progress bar เมื่อกำลังทำงาน --}}
        @if ($isRunning && $latestJob)
            <div class="mt-5">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5">
                    <span>{{ $latestJob->current_stage ?? 'กำลังประมวลผล...' }}</span>
                    <span>{{ $latestJob->progress_percent }}%</span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-indigo-500 rounded-full transition-all"
                        style="width: {{ $latestJob->progress_percent }}%"></div>
                </div>
            </div>
        @endif
    </div>

    {{-- Tabs --}}
    <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm">
        <div class="flex items-center gap-1 px-3 sm:px-5 pt-3 border-b border-slate-100 overflow-x-auto" id="detail-tabs">
            <button data-tab="overview"
                class="detail-tab whitespace-nowrap px-3 py-2.5 text-sm font-medium border-b-2 border-indigo-600 text-indigo-600">Overview</button>
            <button data-tab="leads"
                class="detail-tab whitespace-nowrap px-3 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">Leads</button>
            <button data-tab="criteria"
                class="detail-tab whitespace-nowrap px-3 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">Search
                Criteria</button>
            {{-- <button data-tab="activity" class="detail-tab whitespace-nowrap px-3 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">Activity Logs</button> --}}
        </div>

        <div class="p-4 sm:p-6">

            {{-- ===== TAB: Overview ===== --}}
            <section data-tab-panel="overview" class="tab-panel space-y-5">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-slate-50 rounded-xl p-4">
                        <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3">
                            <i data-lucide="users" class="w-4.5 h-4.5"></i>
                        </div>
                        <p class="text-2xl font-bold text-slate-800">{{ $leadStats['total'] }}</p>
                        <p class="text-xs text-slate-500 mt-1">Lead ที่พบทั้งหมด</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4">
                        <div
                            class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                            <i data-lucide="globe" class="w-4.5 h-4.5"></i>
                        </div>
                        <p class="text-2xl font-bold text-slate-800">{{ $leadStats['with_website'] }}</p>
                        <p class="text-xs text-slate-500 mt-1">มีเว็บไซต์</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4">
                        <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-3"><i
                                data-lucide="phone" class="w-4.5 h-4.5"></i></div>
                        <p class="text-2xl font-bold text-slate-800">{{ $leadStats['with_phone'] }}</p>
                        <p class="text-xs text-slate-500 mt-1">มีเบอร์โทร</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4">
                        <div class="w-9 h-9 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center mb-3"><i
                                data-lucide="alert-triangle" class="w-4.5 h-4.5"></i></div>
                        <p class="text-2xl font-bold text-rose-600">{{ $leadStats['high_severity_issue_count'] }}</p>
                        <p class="text-xs text-slate-500 mt-1">เว็บมีปัญหาระดับสูง</p>
                    </div>
                </div>

                <div class="rounded-xl bg-slate-50 p-4">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">สถานะการรัน Research Job
                        ล่าสุด</h4>
                    @if ($latestJob)
                        <div class="grid sm:grid-cols-3 gap-4 text-sm">
                            <div><span class="text-slate-500">สถานะ</span>
                                <p class="font-medium text-slate-800">{{ $latestJob->status }}</p>
                            </div>
                            <div><span class="text-slate-500">Stage ปัจจุบัน</span>
                                <p class="font-medium text-slate-800">{{ $latestJob->current_stage ?? '-' }}</p>
                            </div>
                            <div><span class="text-slate-500">ประมวลผลแล้ว</span>
                                <p class="font-medium text-slate-800">{{ $latestJob->processed_items }} /
                                    {{ $latestJob->total_items ?? '-' }}</p>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-slate-400">ยังไม่เคยรัน Research Job สำหรับ Campaign นี้</p>
                    @endif
                </div>
            </section>

            {{-- ===== TAB: Leads (preview 10 รายการล่าสุด) ===== --}}
            <section data-tab-panel="leads" class="tab-panel hidden space-y-4">


                <div class="rounded-xl ring-1 ring-slate-100 overflow-hidden">
                    <table class="w-full text-sm table-fixed">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr class="text-left text-slate-500 text-xs uppercase tracking-wide">
                                <th class="px-4 py-2.5 font-bold w-1/5">COMPANY</th>
                                <th class="px-4 py-2.5 font-bold w-1/5">WEBSITE</th>
                                <th class="px-4 py-2.5 font-bold w-1/6">PHONE</th>
                                <th class="px-4 py-2.5 font-bold w-1/5">ประเภทธุรกิจ</th>
                                <th class="px-4 py-2.5 font-bold w-1/6">สถานะ LEAD</th>
                                <th class="px-4 py-2.5 w-16"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($leadsPreview as $lead)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2.5 font-medium text-slate-800 truncate"
                                        title="{{ $lead->company_name }}">
                                        {{ $lead->company_name }}
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-500 truncate" title="{{ $lead->website_url }}">
                                        {{ $lead->website_url ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-500 truncate" title="{{ $lead->phone }}">
                                        {{ $lead->phone ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2.5 truncate" title="{{ $lead->business_type }}">
                                        {{ $lead->business_type ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <x-status-badge :status="$lead->status->value" />
                                    </td>
                                    <td class="px-4 py-2.5 text-right">
                                        <a href="{{ route('leads.show', $lead) }}"
                                            class="text-indigo-600 hover:text-indigo-700 font-medium">ดูข้อมูล</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-slate-400">
                                        ยังไม่พบ Lead ใน Campaign นี้
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-500">แสดง {{ $leadsPreview->count() }} จาก {{ $leadStats['total'] }}
                        รายการล่าสุด</p>
                    <a href="{{ route('leads.index', $campaign) }}"
                        class="text-sm text-indigo-600 hover:text-indigo-700 font-medium inline-flex items-center gap-1">
                        ดู Leads ทั้งหมด <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </section>

            {{-- ===== TAB: Search Criteria (Read-only) ===== --}}
            <section data-tab-panel="criteria" class="tab-panel hidden">
                <div class="max-w-2xl space-y-1">
                    @foreach ([['ชื่อ Campaign', $campaign->name], ['ประเทศ', $campaign->country], ['จังหวัด', $campaign->province], ['อำเภอ/เขต', $campaign->district], ['รายละเอียดพื้นที่', $campaign->location_text], ['รัศมีค้นหา', $campaign->radius_km ? $campaign->radius_km . ' กิโลเมตร' : null], ['คำค้นหาธุรกิจ', $campaign->business_keyword], ['หมวดหมู่ธุรกิจ', $campaign->business_category], ['จำนวน Lead สูงสุด', $campaign->maximum_leads], ['คะแนนรีวิวขั้นต่ำ', $campaign->minimum_rating], ['จำนวนรีวิวขั้นต่ำ', $campaign->minimum_review_count], ['รวมบริษัทที่มีเว็บไซต์', $campaign->include_businesses_with_website ? 'ใช่' : 'ไม่'], ['รวมบริษัทที่ไม่มีเว็บไซต์', $campaign->include_businesses_without_website ? 'ใช่' : 'ไม่'], ['ภาษาที่ใช้ค้นหา', $campaign->search_language]] as [$label, $value])
                        <div class="flex items-start justify-between gap-4 text-sm py-2 border-b border-slate-50">
                            <span class="text-slate-500 shrink-0">{{ $label }}</span>
                            <span
                                class="text-slate-800 font-medium text-right">{{ $value !== null && $value !== '' ? $value : '-' }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ===== TAB: Activity Logs ===== --}}
            {{-- <section data-tab-panel="activity" class="tab-panel hidden">
                <div class="max-w-2xl space-y-4">
                    @forelse ($campaign->researchJobs as $job)
                        <div class="flex items-start gap-3 text-sm">
                            <span class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                <i data-lucide="{{ match(true) {
                                    $job->status === 'completed' => 'check-circle-2',
                                    $job->status === 'failed' => 'x-circle',
                                    default => 'activity',
                                } }}" class="w-4 h-4"></i>
                            </span>
                            <div>
                                <p class="text-slate-800 font-medium">Research Job — {{ $job->status }}</p>
                                <p class="text-slate-500">{{ $job->current_stage ?? '-' }} · ประมวลผล {{ $job->processed_items }}/{{ $job->total_items ?? '-' }} รายการ</p>
                                @if ($job->error_message)
                                    <p class="text-rose-600 mt-0.5">{{ $job->error_message }}</p>
                                @endif
                                <p class="text-xs text-slate-400 mt-1">{{ $job->created_at->format('Y-m-d H:i') }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400 text-center py-8">ยังไม่มีประวัติการรัน Job สำหรับ Campaign นี้</p>
                    @endforelse
                </div>
            </section> --}}

        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.detail-tab').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.detail-tab').forEach((b) => {
                    b.classList.remove('border-indigo-600', 'text-indigo-600');
                    b.classList.add('border-transparent', 'text-slate-500');
                });
                btn.classList.add('border-indigo-600', 'text-indigo-600');
                btn.classList.remove('border-transparent', 'text-slate-500');

                document.querySelectorAll('.tab-panel').forEach((p) => p.classList.add('hidden'));
                document.querySelector(`[data-tab-panel="${btn.dataset.tab}"]`)?.classList.remove('hidden');
            });
        });
    </script>
@endpush
