@extends('layouts.app')

@php
    $activePage = 'campaigns';
    $pageTitle = $lead->company_name;
    $latestAudit = $lead->audits->first();
    // $raw = is_array($lead->raw_source_data)
    //     ? $lead->raw_source_data
    //     : (json_decode((string) $lead->raw_source_data, true) ?:
    //     []);
    $raw = $lead->raw_source_data ?? [];
@endphp

@section('title', $lead->company_name)

@section('content')

    <a href="{{ route('leads.index', $lead->campaign) }}"
        class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-indigo-600">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> กลับไปหน้า Leads
    </a>

    {{-- Header --}}
    <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex items-start gap-4">
                <span
                    class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold shrink-0">
                    {{ mb_substr($lead->company_name, 0, 1) }}
                </span>
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-800">{{ $lead->company_name }}</h1>
                        <x-status-badge :status="$lead->status->value" />
                    </div>
                    <p class="text-sm text-slate-500 mt-0.5">{{ $lead->business_type }}</p>
                    {{-- <p class="text-xs text-slate-400 mt-1 font-mono">Source: {{ $lead->source }} @if ($lead->source_place_id)
                            · {{ $lead->source_place_id }}
                        @endif
                    </p> --}}

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 text-sm">
                        <div>
                            <p class="text-xs text-slate-400">ประเภทธุรกิจ</p>
                            <p class="font-medium text-slate-700 mt-0.5">{{ $lead->business_type ?? '-' }}</p>
                        </div>
                        {{-- <div>
                            <p class="text-xs text-slate-400">จังหวัด</p>
                            <p class="font-medium text-slate-700 mt-0.5">{{ $lead->province }} {{ $lead->district }}</p>
                        </div> --}}
                        <div>
                            <p class="text-xs text-slate-400">ค้นพบเมื่อ</p>
                            <p class="font-medium text-slate-700 mt-0.5">
                                {{ optional($lead->discovered_at)->format('Y-m-d') ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Campaign</p>
                            <p class="font-medium mt-0.5">
                                <a href="{{ route('campaigns.show', $lead->campaign) }}"
                                    class="text-indigo-600 hover:underline">{{ $lead->campaign->name }}</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('leads.update-status', $lead) }}"
                class="flex items-center gap-2 shrink-0">
                @csrf @method('PATCH')
                <select name="status" class="text-sm rounded-lg border border-slate-200 px-3 py-2 outline-none">
                    @foreach (\App\Enums\LeadStatus::cases() as $status)
                        <option value="{{ $status->value }}"
                            {{ $lead->status->value === $status->value ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $status->value)) }}
                        </option>
                    @endforeach
                </select>
                <button type="submit"
                    class="text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg px-3.5 py-2">
                    อัปเดต
                </button>
            </form>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm">
        <div class="flex items-center gap-1 px-3 sm:px-5 pt-3 border-b border-slate-100 overflow-x-auto" id="detail-tabs">
            <button data-tab="overview"
                class="detail-tab whitespace-nowrap px-3 py-2.5 text-sm font-medium border-b-2 border-indigo-600 text-indigo-600">Overview</button>
            {{-- <button data-tab="dbd"
                class="detail-tab whitespace-nowrap px-3 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">DBD
                Information</button> --}}
            <button data-tab="serpapi"
                class="detail-tab whitespace-nowrap px-3 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">SerpApi
                Information</button>
            <button data-tab="contact"
                class="detail-tab whitespace-nowrap px-3 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">Contact
                Information</button>
            <button data-tab="website"
                class="detail-tab whitespace-nowrap px-3 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">Website
                Analysis</button>
            {{-- <button data-tab="lead"
                class="detail-tab whitespace-nowrap px-3 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">Lead
                Analysis</button> --}}
            {{-- <button data-tab="activity"
                class="detail-tab whitespace-nowrap px-3 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">Activity
                Timeline</button> --}}
        </div>

        <div class="p-4 sm:p-6">

            {{-- ===== TAB: Overview ===== --}}
            <section data-tab-panel="overview" class="tab-panel space-y-5">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- <div class="bg-slate-50 rounded-xl p-4">
                        <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center mb-3"><i
                                data-lucide="star" class="w-4.5 h-4.5"></i></div>
                        <p class="text-2xl font-bold text-slate-800">{{ $lead->rating ?? '-' }}</p>
                        <p class="text-xs text-slate-500 mt-1">คะแนนรีวิว ({{ $lead->review_count ?? 0 }} รีวิว)</p>
                    </div> --}}
                    <div class="bg-slate-50 rounded-xl p-4">
                        <div
                            class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                            <i data-lucide="globe" class="w-4.5 h-4.5"></i>
                        </div>
                        <p class="text-2xl font-bold text-slate-800">{{ $lead->website_url ? 'Online' : 'ไม่พบ' }}</p>
                        <p class="text-xs text-slate-500 mt-1">สถานะเว็บไซต์</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4">
                        <div class="w-9 h-9 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center mb-3"><i
                                data-lucide="alert-triangle" class="w-4.5 h-4.5"></i></div>
                        <p class="text-2xl font-bold text-slate-800">{{ $lead->audits->count() }}</p>
                        <p class="text-xs text-slate-500 mt-1">จำนวนครั้งที่ตรวจสอบเว็บไซต์</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4">
                        <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3">
                            <i data-lucide="message-square" class="w-4.5 h-4.5"></i>
                        </div>
                        <p class="text-2xl font-bold text-slate-800">{{ $lead->notes->count() }}</p>
                        <p class="text-xs text-slate-500 mt-1">บันทึกภายใน</p>
                    </div>
                </div>

                <div class="grid lg:grid-cols-2 gap-5">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">ข้อมูลทั่วไป</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">ที่อยู่</span><span
                                    class="text-slate-800 font-medium text-right">{{ $lead->address ?? '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">พิกัด</span><span
                                    class="text-slate-800 font-medium">{{ $lead->latitude }},
                                    {{ $lead->longitude }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">ผู้รับผิดชอบ</span><span
                                    class="text-slate-800 font-medium">{{ $lead->assignedUser->name ?? 'ยังไม่มอบหมาย' }}</span>
                            </div>
                        </div>
                    </div>
                    {{-- <div class="rounded-xl bg-slate-50 p-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">ผลตรวจสอบล่าสุด</h4>
                        @if ($latestAudit)
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between"><span class="text-slate-500">Audit Score</span><span
                                        class="font-medium {{ $latestAudit->audit_score < 40 ? 'text-rose-600' : 'text-slate-800' }}">{{ $latestAudit->audit_score }}</span>
                                </div>
                                <div class="flex justify-between"><span class="text-slate-500">HTTPS</span><span
                                        class="text-slate-800 font-medium">{{ $latestAudit->https_enabled ? 'ใช้งาน' : 'ไม่ใช้งาน' }}</span>
                                </div>
                                <div class="flex justify-between"><span class="text-slate-500">ตรวจเมื่อ</span><span
                                        class="text-slate-800 font-medium">{{ optional($latestAudit->audited_at)->format('Y-m-d H:i:s') ?? '-' }}</span>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-slate-400">ยังไม่มีผลตรวจสอบเว็บไซต์</p>
                        @endif
                    </div> --}}
                </div>
            </section>

            {{-- ===== TAB: DBD Information ===== --}}
            {{-- <section data-tab-panel="dbd" class="tab-panel hidden space-y-5">
                <div class="rounded-lg bg-slate-50 text-slate-500 text-xs font-medium px-3 py-2.5 flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4 shrink-0"></i>
                    ระบบปัจจุบันยังไม่ได้เชื่อมต่อข้อมูลนิติบุคคลจากกรมพัฒนาธุรกิจการค้า (DBD) —
                    แสดงเฉพาะข้อมูลดิบที่ได้จากแหล่งค้นหา ({{ $lead->source }}) เท่านั้น
                </div>

                @if (!empty($raw))
                    <div class="rounded-xl bg-slate-50 p-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">
                            ข้อมูลดิบจากแหล่งค้นหา (raw_source_data)</h4>
                        <div class="space-y-2 text-sm">
                            @foreach ($raw as $key => $value)
                                @if (!is_array($value))
                                    <div class="flex justify-between gap-4"><span
                                            class="text-slate-500 shrink-0">{{ $key }}</span><span
                                            class="text-slate-800 font-medium text-right break-all">{{ $value }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="rounded-xl bg-slate-50 p-8 text-center">
                        <i data-lucide="file-search" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                        <p class="text-sm text-slate-400">ไม่มีข้อมูลนิติบุคคลสำหรับ Lead นี้</p>
                    </div>
                @endif
            </section> --}}
            {{-- ===== TAB: DBD Information ===== --}}
            <section data-tab-panel="serpapi" class="tab-panel hidden space-y-5">
                @if (!empty($raw))
                    {{-- ส่วนที่ 1: ข้อมูลสำคัญ จัดรูปแบบให้อ่านง่าย --}}
                    <div class="rounded-xl bg-slate-50 p-4 space-y-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400">ข้อมูลจาก Google Maps</h4>

                        <dl class="space-y-2 text-sm">
                            @if (isset($raw['rating']))
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">คะแนนรีวิว</dt>
                                    <dd class="font-medium text-slate-800">⭐ {{ $raw['rating'] }}
                                        ({{ $raw['reviews'] ?? 0 }} รีวิว)</dd>
                                </div>
                            @endif

                            @if (!empty($raw['types']))
                                <div class="flex justify-between gap-4">
                                    <dt class="text-slate-500 shrink-0">ประเภทธุรกิจ</dt>
                                    <dd class="flex flex-wrap gap-1 justify-end">
                                        @foreach ($raw['types'] as $type)
                                            <span
                                                class="text-xs bg-white text-slate-600 ring-1 ring-slate-200 px-2 py-0.5 rounded-full">{{ $type }}</span>
                                        @endforeach
                                    </dd>
                                </div>
                            @elseif (isset($raw['type']))
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">ประเภทธุรกิจ</dt>
                                    <dd class="font-medium text-slate-800">{{ $raw['type'] }}</dd>
                                </div>
                            @endif

                            @if (isset($raw['open_state']))
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">สถานะร้าน</dt>
                                    <dd class="font-medium text-slate-800 text-right">{{ $raw['open_state'] }}</dd>
                                </div>
                            @endif

                            @if (isset($raw['gps_coordinates']['latitude'], $raw['gps_coordinates']['longitude']))
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">พิกัด</dt>
                                    <dd>
                                        <a href="https://www.google.com/maps?q={{ $raw['gps_coordinates']['latitude'] }},{{ $raw['gps_coordinates']['longitude'] }}"
                                            target="_blank" rel="noopener" class="text-indigo-600 hover:underline">
                                            เปิดใน Google Maps
                                        </a>
                                    </dd>
                                </div>
                            @endif
                        </dl>

                        @php
                            $dayOrder = [
                                'จันทร์' => 0,
                                'อังคาร' => 1,
                                'พุธ' => 2,
                                'พฤหัสบดี' => 3,
                                'ศุกร์' => 4,
                                'เสาร์' => 5,
                                'อาทิตย์' => 6,
                            ];

                            $sortedHours = collect($raw['operating_hours'] ?? [])
                                ->sortBy(fn($hours, $day) => $dayOrder[$day] ?? 99)
                                ->all();
                        @endphp

                        @if (!empty($sortedHours))
                            <div>
                                <h5 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">เวลาทำการ
                                </h5>
                                <dl class="space-y-1 text-sm">
                                    @foreach ($sortedHours as $day => $hours)
                                        <div class="flex justify-between">
                                            <dt class="text-slate-500">{{ $day }}</dt>
                                            <dd class="{{ $hours === 'ปิดทำการ' ? 'text-slate-400' : 'text-slate-800' }}">
                                                {{ $hours }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @endif
                    </div>

                    {{-- ส่วนที่ 2: field อื่นๆ ที่ไม่ได้จัดรูปแบบเฉพาะ เรียงตาม priority --}}
                    @php
                        // field ที่จัดรูปแบบเฉพาะไปแล้ว + field internal ที่ไม่ต้องการแสดงเลย
                        $handledKeys = [
                            // จัดรูปแบบสวยไปแล้วด้านบน
                            'rating',
                            'reviews',
                            'types',
                            'type',
                            'open_state',
                            'gps_coordinates',
                            'operating_hours',

                            // internal reference — ไม่ต้องการแสดง
                            'position',
                            'place_id',
                            'data_id',
                            'data_cid',
                            'provider_id',
                            'thumbnail',
                            'serpapi_thumbnail',
                            'photos_link',
                            'reviews_link',
                            'place_id_search',
                        ];

                        $remaining = collect($raw)->except($handledKeys);

                        $priorityOrder = ['title', 'phone', 'website', 'address', 'country'];

                        $remainingSorted = $remaining
                            ->sortBy(function ($value, $key) use ($priorityOrder) {
                                $index = array_search($key, $priorityOrder);
                                return $index === false ? 999 : $index;
                            })
                            ->all();
                    @endphp

                    @if (!empty($remainingSorted))
                        <div class="rounded-xl bg-slate-50 p-4">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">ข้อมูลอื่นๆ
                                (raw_source_data)</h4>
                            <div class="space-y-2 text-sm">
                                @foreach ($remainingSorted as $key => $value)
                                    <div class="flex justify-between gap-4">
                                        <span class="text-slate-500 shrink-0">{{ $key }}</span>
                                        <span class="text-slate-800 font-medium text-right break-all">
                                            @if (is_array($value))
                                                <span
                                                    class="text-xs font-mono text-slate-400">{{ json_encode($value, JSON_UNESCAPED_UNICODE) }}</span>
                                            @else
                                                {{ $value }}
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <div class="rounded-xl bg-slate-50 p-8 text-center">
                        <i data-lucide="file-search" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                        <p class="text-sm text-slate-400">ไม่มีข้อมูลสำหรับ Lead นี้</p>
                    </div>
                @endif
            </section>

            {{-- ===== TAB: Contact Information ===== --}}
            <section data-tab-panel="contact" class="tab-panel hidden space-y-5">
                <div class="rounded-xl bg-slate-50 p-4">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">ข้อมูลการติดต่อ</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-slate-500">Website</span>
                            <span class="text-slate-800 font-medium">
                                @if ($lead->website_url)
                                    <a href="{{ $lead->website_url }}" target="_blank"
                                        class="text-indigo-600 hover:text-indigo-700">{{ $lead->normalized_domain ?? $lead->website_url }}</a>
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between"><span class="text-slate-500">Phone</span>
                            <span class="text-slate-800 font-medium">
                                @if ($lead->phone)
                                    <a href="tel:{{ $lead->phone }}"
                                        class="text-indigo-600 hover:text-indigo-700">{{ $lead->phone }}</a>
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between"><span class="text-slate-500">Address</span><span
                                class="text-slate-800 font-medium text-right">{{ $lead->address ?? '-' }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Province / District</span><span
                                class="text-slate-800 font-medium">{{ $lead->province }} {{ $lead->district }}</span>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-3">* Email, LINE ID และ Social Media
                        ยังไม่มีคอลัมน์เก็บข้อมูลในระบบปัจจุบัน (ตาราง <code class="bg-white px-1 rounded">leads</code>) —
                        ต้องเพิ่ม migration หากต้องการเก็บข้อมูลเหล่านี้</p>
                </div>
            </section>

            {{-- ===== TAB: Website Analysis ===== --}}
            <section data-tab-panel="website" class="tab-panel hidden space-y-5">
                @if ($latestAudit)
                    {{-- <div class="grid sm:grid-cols-3 gap-4">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs text-slate-500">Audit Score</p>
                            <p
                                class="text-2xl font-bold {{ $latestAudit->audit_score < 40 ? 'text-rose-600' : 'text-slate-800' }} mt-1">
                                {{ $latestAudit->audit_score }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs text-slate-500">Confidence Score</p>
                            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $latestAudit->confidence_score ?? '-' }}
                            </p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs text-slate-500">Response Time</p>
                            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $latestAudit->response_time_ms ?? '-' }}
                                ms</p>
                        </div>
                    </div> --}}

                    {{-- <div class="rounded-xl bg-slate-50 p-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">รายละเอียดทางเทคนิค
                        </h4>
                        <div class="grid sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-slate-500">HTTP Status</span><span
                                    class="text-slate-800 font-medium">{{ $latestAudit->http_status ?? '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">HTTPS</span><span
                                    class="text-slate-800 font-medium">{{ $latestAudit->https_enabled ? 'มี' : 'ไม่มี' }}</span>
                            </div>
                            <div class="flex justify-between"><span class="text-slate-500">SSL Valid</span><span
                                    class="text-slate-800 font-medium">{{ $latestAudit->ssl_valid ? 'ใช่' : 'ไม่' }}</span>
                            </div>
                            <div class="flex justify-between"><span class="text-slate-500">Mobile Viewport</span><span
                                    class="text-slate-800 font-medium">{{ $latestAudit->mobile_viewport_found ? 'พบ' : 'ไม่พบ' }}</span>
                            </div>
                            <div class="flex justify-between"><span class="text-slate-500">Title</span><span
                                    class="text-slate-800 font-medium">{{ $latestAudit->title_found ? 'พบ' : 'ไม่พบ' }}</span>
                            </div>
                            <div class="flex justify-between"><span class="text-slate-500">Meta Description</span><span
                                    class="text-slate-800 font-medium">{{ $latestAudit->meta_description_found ? 'พบ' : 'ไม่พบ' }}</span>
                            </div>
                            <div class="flex justify-between"><span class="text-slate-500">ฟอร์มติดต่อ</span><span
                                    class="text-slate-800 font-medium">{{ $latestAudit->contact_form_found ? 'มี' : 'ไม่มี' }}</span>
                            </div>
                            <div class="flex justify-between"><span class="text-slate-500">ลิงก์เสีย</span><span
                                    class="text-slate-800 font-medium">{{ $latestAudit->broken_link_count ?? 0 }}</span>
                            </div>
                        </div>
                    </div> --}}

                    <div class="rounded-xl bg-slate-50 p-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">ปัญหาที่พบ</h4>
                        @if (!empty($latestAudit->issues))
                            @php
                                $severityRank = [
                                    'critical' => 0,
                                    'high' => 1,
                                    'medium' => 2,
                                    'low' => 3,
                                    'opportunity' => 4,
                                ];
                                $severityStyle = [
                                    'critical' => 'text-red-600',
                                    'high' => 'text-red-500',
                                    'medium' => 'text-amber-500',
                                    'low' => 'text-slate-400',
                                    'opportunity' => 'text-emerald-500',
                                ];
                                $severityIcon = [
                                    'critical' => 'alert-octagon',
                                    'high' => 'alert-triangle',
                                    'medium' => 'alert-triangle',
                                    'low' => 'info',
                                    'opportunity' => 'lightbulb',
                                ];

                                $sortedIssues = collect($latestAudit->issues)
                                    ->sortBy(fn($issue) => $severityRank[$issue['severity'] ?? 'low'] ?? 99)
                                    ->values();
                            @endphp
                            <div class="space-y-2">
                                @foreach ($sortedIssues as $issue)
                                    <div
                                        class="flex items-center justify-between bg-white rounded-lg px-3 py-2.5 ring-1 ring-slate-100 text-sm">
                                        <span class="text-slate-700">{{ $issue['message_th'] ?? '-' }}</span>
                                        <i data-lucide="{{ $severityIcon[$issue['severity'] ?? 'medium'] ?? 'alert-triangle' }}"
                                            class="w-4 h-4 {{ $severityStyle[$issue['severity'] ?? 'medium'] ?? 'text-amber-500' }}"></i>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-slate-400">ไม่พบปัญหาที่มีนัยสำคัญจากการตรวจสอบ</p>
                        @endif
                    </div>
                @else
                    <div class="rounded-xl bg-slate-50 p-8 text-center">
                        <i data-lucide="globe" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                        <p class="text-sm text-slate-400">
                            {{ $lead->website_url ? 'ยังไม่มีผลตรวจสอบเว็บไซต์' : 'บริษัทนี้ไม่มีเว็บไซต์' }}</p>
                    </div>
                @endif
            </section>

            {{-- ===== TAB: Lead Analysis ===== --}}
            {{-- <section data-tab-panel="lead" class="tab-panel hidden space-y-5">
                <div class="rounded-lg bg-violet-50 text-violet-700 text-xs font-medium px-3 py-2 flex items-center gap-2">
                    <i data-lucide="sparkles" class="w-4 h-4 shrink-0"></i>
                    ข้อมูลในแท็บนี้อ้างอิงผลตรวจสอบเว็บไซต์ (Website Audit) เพื่อประกอบการตัดสินใจเบื้องต้นเท่านั้น
                    การวิเคราะห์ Lead ด้วย AI แบบเต็มรูปแบบ (Opportunity, Recommended Services) ยังไม่ได้เชื่อมต่อจาก Python
                    Agent
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-slate-50 rounded-xl p-4">
                        <p class="text-2xl font-bold text-slate-800">{{ $latestAudit->audit_score ?? '-' }}</p>
                        <p class="text-xs text-slate-500 mt-1">Website Audit Score</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4">
                        <p class="text-2xl font-bold text-slate-800">
                            {{ $latestAudit && $latestAudit->confidence_score ? round($latestAudit->confidence_score * 100) . '%' : '-' }}
                        </p>
                        <p class="text-xs text-slate-500 mt-1">ระดับความมั่นใจ</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4">
                        <p class="text-2xl font-bold text-slate-800">{{ $lead->rating ?? '-' }}</p>
                        <p class="text-xs text-slate-500 mt-1">คะแนนรีวิว Google</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4">
                        <p class="text-2xl font-bold text-slate-800">{{ $lead->review_count ?? 0 }}</p>
                        <p class="text-xs text-slate-500 mt-1">จำนวนรีวิว</p>
                    </div>
                </div>

                <div class="rounded-xl bg-slate-50 p-4">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">ข้อสังเกตเบื้องต้น</h4>
                    <ul class="text-sm text-slate-700 space-y-1.5 list-disc list-inside">
                        @if (!$lead->website_url)
                            <li>บริษัทนี้ยังไม่มีเว็บไซต์ — อาจเป็นโอกาสในการเสนอบริการทำเว็บไซต์</li>
                        @elseif ($latestAudit && $latestAudit->audit_score < 40)
                            <li>เว็บไซต์ปัจจุบันมีปัญหาระดับสูง (คะแนน {{ $latestAudit->audit_score }}) —
                                อาจเป็นโอกาสเสนอบริการปรับปรุงเว็บไซต์</li>
                        @else
                            <li>ยังไม่มีข้อบ่งชี้พิเศษจากข้อมูลที่มีอยู่ในระบบขณะนี้</li>
                        @endif
                    </ul>
                </div>
            </section> --}}

            {{-- ===== TAB: Activity Timeline ===== --}}
            {{-- <section data-tab-panel="activity" class="tab-panel hidden">
                <div class="max-w-2xl space-y-4">
                    @php
                        $timeline = collect()
                            ->concat(
                                $lead->audits->map(
                                    fn($a) => [
                                        'at' => $a->audited_at,
                                        'icon' => 'globe',
                                        'text' => 'ตรวจสอบเว็บไซต์ — คะแนน ' . $a->audit_score,
                                    ],
                                ),
                            )
                            ->concat(
                                $lead->notes->map(
                                    fn($n) => [
                                        'at' => $n->created_at,
                                        'icon' => 'message-square',
                                        'text' => 'บันทึก: ' . $n->note,
                                        'user' => $n->user->name ?? null,
                                    ],
                                ),
                            )
                            ->push([
                                'at' => $lead->discovered_at,
                                'icon' => 'sparkles',
                                'text' => 'ค้นพบ Lead นี้จาก ' . $lead->source,
                            ])
                            ->filter(fn($e) => $e['at'] !== null)
                            ->sortByDesc('at');
                    @endphp

                    @forelse ($timeline as $event)
                        <div class="flex items-start gap-3 text-sm">
                            <span
                                class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                <i data-lucide="{{ $event['icon'] }}" class="w-4 h-4"></i>
                            </span>
                            <div>
                                <p class="text-slate-800">{{ $event['text'] }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    {{ \Illuminate\Support\Carbon::parse($event['at'])->format('Y-m-d H:i') }}
                                    @isset($event['user'])
                                        · {{ $event['user'] }}
                                    @endisset
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400 text-center py-8">ยังไม่มีประวัติสำหรับ Lead นี้</p>
                    @endforelse
                </div>
            </section> --}}

        </div>
    </div>

    {{-- Notes (เพิ่มบันทึกใหม่) --}}
    <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-5">
        <h2 class="font-semibold text-slate-800 mb-3">เพิ่มบันทึกภายใน</h2>
        <form method="POST" action="{{ route('leads.notes.store', $lead) }}" class="flex items-start gap-2">
            @csrf
            <textarea name="note" rows="2" placeholder="เพิ่มบันทึกเกี่ยวกับ Lead นี้..."
                class="flex-1 text-sm rounded-lg border border-slate-200 px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500/30"></textarea>
            <button type="submit"
                class="text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg px-4 py-2.5 shrink-0">บันทึก</button>
        </form>
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
