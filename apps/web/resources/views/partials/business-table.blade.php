{{--
  partials/business-table.blade.php
  ตาราง Lead แบบเต็ม (รูปที่ 1) — ถอดจาก businessRowHtml() + businessCardHtml() ใน app.js ของ Demo
  ใช้ได้ทั้งในแท็บ Leads ของหน้า campaign-detail และหน้า Leads รวม

  วิธีใช้:
    @include('partials.business-table', ['leads' => $leads])
    (ถ้าต้องการปิดแถบค้นหา/Export ด้านบน ส่ง 'showToolbar' => false)

  $leads : array/collection ของ Lead แต่ละตัวต้องมี key ต่อไปนี้ (ชื่อตรงกับ mock-data.js)
    id, company_name_th, company_name_en, website, phone, phone_secondary_count,
    province, business_type, registration_number, lead_status, data_completeness, created_at
--}}
@php
  $showToolbar = $showToolbar ?? true;

  // LEAD_STATUS_META (ตรงกับ mock-data.js) — สี badge ตามสถานะ Lead
  $leadStatusMeta = [
    'new'       => ['label' => 'New',       'color' => 'bg-slate-100 text-slate-600'],
    'reviewed'  => ['label' => 'Reviewed',  'color' => 'bg-sky-50 text-sky-700'],
    'contacted' => ['label' => 'Contacted', 'color' => 'bg-indigo-50 text-indigo-700'],
    'qualified' => ['label' => 'Qualified', 'color' => 'bg-violet-50 text-violet-700'],
    'follow_up' => ['label' => 'Follow Up', 'color' => 'bg-amber-50 text-amber-700'],
    'proposal'  => ['label' => 'Proposal',  'color' => 'bg-purple-50 text-purple-700'],
    'won'       => ['label' => 'Won',        'color' => 'bg-emerald-50 text-emerald-700'],
    'lost'      => ['label' => 'Lost',       'color' => 'bg-rose-50 text-rose-700'],
    'rejected'  => ['label' => 'Rejected',   'color' => 'bg-slate-200 text-slate-500'],
  ];

  $thaiMonthShort = [1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'];

  // helper ระดับ presentation (เทียบเท่า formatThaiDate / shortDomain / completenessRing / initialsAvatar ใน Demo)
  $thaiDate = function ($dateStr) use ($thaiMonthShort) {
    if (empty($dateStr)) return '-';
    $ts = strtotime((string) $dateStr);
    if ($ts === false) return $dateStr;
    return sprintf('%02d', (int) date('j', $ts)) . ' ' . $thaiMonthShort[(int) date('n', $ts)] . ' ' . ((int) date('Y', $ts) + 543);
  };

  $shortDomain = function ($url) {
    if (empty($url)) return '';
    $host = parse_url($url, PHP_URL_HOST) ?: $url;
    return preg_replace('/^www\./', '', $host);
  };

  $ringColor = fn ($p) => $p >= 80 ? '#10b981' : ($p >= 50 ? '#f59e0b' : '#f43f5e');
  $ringCirc  = 2 * M_PI * 14; // r = 14 (ตรงกับ completenessRing)

  $businessUrl = fn ($id) => \Illuminate\Support\Facades\Route::has('business.show')
    ? route('business.show', ['business' => $id])
    : url('/businesses/' . $id);
@endphp

<div class="space-y-4">

  @if ($showToolbar)
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="relative flex-1 max-w-sm">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
        <input id="campaign-leads-search" type="text" placeholder="ค้นหาชื่อบริษัท..."
               class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none transition" />
      </div>
      <button id="campaign-leads-export" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-lg px-3 py-2 shrink-0">
        <i data-lucide="download" class="w-4 h-4"></i> Export CSV
      </button>
    </div>
  @endif

  {{-- Desktop table --}}
  <div class="bg-white rounded-xl ring-1 ring-slate-100 overflow-hidden hidden md:block">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50">
          <tr class="text-left text-slate-500 text-xs uppercase tracking-wide">
            <th class="px-4 py-3">Company</th>
            <th class="px-4 py-3">Website</th>
            <th class="px-4 py-3">Phone</th>
            <th class="px-4 py-3">จังหวัด</th>
            <th class="px-4 py-3">ประเภทธุรกิจ</th>
            <th class="px-4 py-3">เลขทะเบียนนิติบุคคล</th>
            <th class="px-4 py-3">สถานะ Lead</th>
            <th class="px-4 py-3">ความสมบูรณ์</th>
            <th class="px-4 py-3">วันที่ค้นพบ</th>
            <th class="px-4 py-3 w-16"></th>
          </tr>
        </thead>
        <tbody id="campaign-leads-tbody" class="divide-y divide-slate-50">
          @forelse ($leads as $b)
            @php
              $status = data_get($b, 'lead_status');
              $meta = $leadStatusMeta[$status] ?? ['label' => $status, 'color' => 'bg-slate-100 text-slate-600'];
              $percent = (int) data_get($b, 'data_completeness', 0);
              $website = data_get($b, 'website');
              $phone = data_get($b, 'phone');
              $phoneSecondary = (int) data_get($b, 'phone_secondary_count', 0);
              $nameEn = data_get($b, 'company_name_en');
              $url = $businessUrl(data_get($b, 'id'));
            @endphp
            <tr class="lead-row transition-colors" data-business-id="{{ data_get($b, 'id') }}">
              <td class="px-4 py-3 max-w-[220px]">
                <div class="flex items-center gap-2.5">
                  <span class="w-9 h-9 text-xs rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-semibold shrink-0">{{ mb_strtoupper(mb_substr(trim((string) data_get($b, 'company_name_th', '?')), 0, 2)) }}</span>
                  <div class="min-w-0">
                    <a href="{{ $url }}" class="font-medium text-slate-800 hover:text-indigo-600 truncate block" title="{{ data_get($b, 'company_name_th') }}">{{ data_get($b, 'company_name_th') }}</a>
                    @if ($nameEn)
                      <p class="text-xs text-slate-400 truncate">{{ $nameEn }}</p>
                    @endif
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                @if ($website)
                  <a href="{{ $website }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 text-xs font-medium">{{ $shortDomain($website) }} <i data-lucide="external-link" class="w-3 h-3"></i></a>
                @else
                  <span class="text-slate-400 text-xs">ไม่พบเว็บไซต์</span>
                @endif
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                @if ($phone)
                  <a href="tel:{{ $phone }}" class="text-slate-700 hover:text-indigo-600 text-xs font-medium">{{ $phone }}</a>{!! $phoneSecondary > 0 ? ' <span class="text-slate-400">+' . e($phoneSecondary) . ' เบอร์</span>' : '' !!}
                @else
                  <span class="text-slate-400 text-xs">ไม่พบเบอร์โทร</span>
                @endif
              </td>
              <td class="px-4 py-3 whitespace-nowrap">{{ data_get($b, 'province') }}</td>
              <td class="px-4 py-3 whitespace-nowrap">{{ data_get($b, 'business_type') }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-slate-500" title="{{ data_get($b, 'registration_number') }}">{{ data_get($b, 'registration_number') }}</td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $meta['color'] }} whitespace-nowrap">{{ $meta['label'] }}</span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="relative w-9 h-9 shrink-0" title="ความสมบูรณ์ของข้อมูล {{ $percent }}%">
                  <svg viewBox="0 0 36 36" class="w-9 h-9 -rotate-90">
                    <circle cx="18" cy="18" r="14" stroke="#f1f5f9" stroke-width="4" fill="none" />
                    <circle cx="18" cy="18" r="14" stroke="{{ $ringColor($percent) }}" stroke-width="4" fill="none"
                            stroke-dasharray="{{ $ringCirc }}" stroke-dashoffset="{{ $ringCirc * (1 - $percent / 100) }}" stroke-linecap="round" />
                  </svg>
                  <span class="absolute inset-0 flex items-center justify-center text-[9px] font-semibold text-slate-600">{{ $percent }}</span>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-slate-500">{{ $thaiDate(data_get($b, 'created_at')) }}</td>
              <td class="px-4 py-3 text-right">
                <a href="{{ $url }}" class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 border border-indigo-100 hover:bg-indigo-50 rounded-lg px-2.5 py-1.5">
                  <i data-lucide="eye" class="w-3.5 h-3.5"></i> ดูข้อมูล
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10">
                <div class="flex flex-col items-center justify-center py-14 text-center">
                  <i data-lucide="inbox" class="w-9 h-9 text-slate-300 mb-3"></i>
                  <p class="text-slate-500 font-medium">ยังไม่พบ Lead ใน Campaign นี้</p>
                  <p class="text-sm text-slate-400 mt-1">Campaign อาจยังไม่เริ่มประมวลผล หรือยังไม่พบบริษัทที่ตรงเงื่อนไข</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Mobile card list (ถอดจาก businessCardHtml) --}}
  <div id="campaign-leads-cardlist" class="md:hidden space-y-3">
    @forelse ($leads as $b)
      @php
        $status = data_get($b, 'lead_status');
        $meta = $leadStatusMeta[$status] ?? ['label' => $status, 'color' => 'bg-slate-100 text-slate-600'];
        $website = data_get($b, 'website');
        $phone = data_get($b, 'phone');
        $phoneSecondary = (int) data_get($b, 'phone_secondary_count', 0);
        $nameEn = data_get($b, 'company_name_en');
        $url = $businessUrl(data_get($b, 'id'));
      @endphp
      <div class="bg-white rounded-xl ring-1 ring-slate-100 shadow-sm p-4" data-business-id="{{ data_get($b, 'id') }}">
        <div class="flex items-start gap-3">
          <span class="w-9 h-9 text-xs rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-semibold shrink-0">{{ mb_strtoupper(mb_substr(trim((string) data_get($b, 'company_name_th', '?')), 0, 2)) }}</span>
          <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
              <a href="{{ $url }}" class="font-medium text-slate-800 truncate">{{ data_get($b, 'company_name_th') }}</a>
              <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $meta['color'] }} whitespace-nowrap">{{ $meta['label'] }}</span>
            </div>
            @if ($nameEn)
              <p class="text-xs text-slate-400 truncate mt-0.5">{{ $nameEn }}</p>
            @endif
            <div class="grid grid-cols-2 gap-y-1 mt-3 text-xs text-slate-500">
              <span><i data-lucide="map-pin" class="w-3 h-3 inline mr-1"></i>{{ data_get($b, 'province') }}</span>
              <span><i data-lucide="building-2" class="w-3 h-3 inline mr-1"></i>{{ data_get($b, 'business_type') }}</span>
              <span>
                @if ($website)
                  <a href="{{ $website }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 text-xs font-medium">{{ $shortDomain($website) }} <i data-lucide="external-link" class="w-3 h-3"></i></a>
                @else
                  <span class="text-slate-400 text-xs">ไม่พบเว็บไซต์</span>
                @endif
              </span>
              <span>
                @if ($phone)
                  <a href="tel:{{ $phone }}" class="text-slate-700 hover:text-indigo-600 text-xs font-medium">{{ $phone }}</a>{!! $phoneSecondary > 0 ? ' <span class="text-slate-400">+' . e($phoneSecondary) . ' เบอร์</span>' : '' !!}
                @else
                  <span class="text-slate-400 text-xs">ไม่พบเบอร์โทร</span>
                @endif
              </span>
            </div>
            <div class="flex items-center justify-between mt-3">
              <span class="text-xs text-slate-400">ค้นพบ {{ $thaiDate(data_get($b, 'created_at')) }}</span>
              <a href="{{ $url }}" class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 border border-indigo-100 hover:bg-indigo-50 rounded-lg px-2.5 py-1.5">
                <i data-lucide="eye" class="w-3.5 h-3.5"></i> ดูข้อมูล
              </a>
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="flex flex-col items-center justify-center py-14 text-center">
        <i data-lucide="inbox" class="w-9 h-9 text-slate-300 mb-3"></i>
        <p class="text-slate-500 font-medium">ยังไม่พบ Lead ใน Campaign นี้</p>
      </div>
    @endforelse
  </div>

</div>
