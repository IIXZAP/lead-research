{{--
  pages/leads.blade.php
  แปลงจาก leads.html (Demo) — Lead Directory รวมบริษัท/Lead จากทุก Campaign
  เนื้อหาภายใน <main> ยกมาจากต้นฉบับตรง ๆ ทั้ง DOM structure / class / id
  Logic ของหน้า (Search, Filter 6 ช่อง, Sort, Pagination, Export CSV, Skeleton,
  Empty/Error state, ตาราง Desktop + Card Mobile) อยู่ใน assets/js/leads.js ต้นฉบับ
  ซึ่งแก้เพียงจุดเดียว: ตัด renderSidebar/renderTopbar (ย้ายไป render ฝั่ง Server แล้ว)
--}}
@extends('layouts.app')

@section('page', 'leads')
@section('title', 'Leads')
@section('main-class', 'space-y-5 max-w-[1600px]')

@section('content')

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Leads</h1>
      <p class="text-sm text-slate-500 mt-0.5">รายชื่อบริษัทและนิติบุคคลที่ค้นพบจากทุก Campaign · <span id="leads-total-count">0</span> รายการทั้งหมด</p>
    </div>
    <div class="flex items-center gap-2">
      <button id="btn-export" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-lg px-3 py-2">
        <i data-lucide="download" class="w-4 h-4"></i> <span class="hidden sm:inline">Export CSV</span>
      </button>
    </div>
  </div>

  <!-- Summary Cards -->
  <div id="lead-summary-cards" class="grid grid-cols-2 lg:grid-cols-4 gap-4"></div>

  <!-- Search & Filters -->
  <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4 space-y-3">
    <div class="flex flex-col lg:flex-row gap-3">
      <div class="relative flex-1">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
        <input id="leads-search" type="text" placeholder="ค้นหาชื่อบริษัท (ไทย/อังกฤษ)..."
               class="w-full pl-9 pr-3 py-2.5 text-sm rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none transition" />
      </div>
      <button id="btn-toggle-filters" class="inline-flex items-center justify-center gap-1.5 text-sm font-medium text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-lg px-3.5 py-2">
        <i data-lucide="sliders-horizontal" class="w-4 h-4"></i> ตัวกรอง
        <span id="active-filter-badge" class="hidden ml-1 bg-indigo-600 text-white text-[10px] rounded-full w-4 h-4 flex items-center justify-center"></span>
      </button>
    </div>

    <div id="filters-panel" class="hidden grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 pt-3 border-t border-slate-100">
      <select id="filter-province" class="filter-input"><option value="">จังหวัดทั้งหมด</option></select>
      <select id="filter-business-type" class="filter-input"><option value="">ประเภทธุรกิจทั้งหมด</option></select>
      <select id="filter-lead-status" class="filter-input"><option value="">สถานะ Lead ทั้งหมด</option></select>
      <select id="filter-has-website" class="filter-input">
        <option value="">Website ทั้งหมด</option>
        <option value="yes">มี Website</option>
        <option value="no">ไม่มี Website</option>
      </select>
      <select id="filter-has-phone" class="filter-input">
        <option value="">เบอร์โทรทั้งหมด</option>
        <option value="yes">มีเบอร์โทร</option>
        <option value="no">ไม่มีเบอร์โทร</option>
      </select>
      <select id="filter-has-email" class="filter-input">
        <option value="">Email ทั้งหมด</option>
        <option value="yes">มี Email</option>
        <option value="no">ไม่มี Email</option>
      </select>
    </div>
    <div id="filters-reset-row" class="hidden justify-end">
      <button id="btn-reset-filters" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">รีเซ็ตตัวกรองทั้งหมด</button>
    </div>
  </div>

  <!-- Table (desktop) -->
  <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm overflow-hidden hidden md:block">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 sticky top-0 z-10">
          <tr id="table-head-row" class="text-left text-slate-500 text-xs uppercase tracking-wide"></tr>
        </thead>
        <tbody id="leads-table-body" class="divide-y divide-slate-50"></tbody>
      </table>
    </div>
  </div>

  <!-- Card list (mobile) -->
  <div id="leads-card-list" class="md:hidden space-y-3"></div>

  <!-- Pagination -->
  <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4">
    <div class="flex items-center gap-2 text-sm text-slate-500">
      <span>แสดง</span>
      <select id="page-size-select" class="filter-input !py-1.5">
        <option value="10">10</option>
        <option value="20" selected>20</option>
        <option value="50">50</option>
      </select>
      <span>รายการต่อหน้า</span>
    </div>
    <p id="pagination-info" class="text-sm text-slate-500"></p>
    <div id="pagination-controls" class="flex items-center gap-1"></div>
  </div>

@endsection

@push('scripts')
  {{-- ข้อมูลผู้ใช้ปัจจุบันสำหรับฝั่ง client (permissions.js ใช้ id + account_type ในการ scope ข้อมูล) --}}
  @php
    $u = auth()->user();
    $clientUser = $u ? [
      'id' => $u->id,
      'name' => $u->name,
      'email' => $u->email,
      'account_type' => $u->account_type ?? 'admin',
      'avatar' => $u->avatar ?? 'https://i.pravatar.cc/150?img=12',
    ] : [
      // Fallback ระหว่างยังไม่ต่อ Auth จริง (ตรงกับ MOCK_USERS[0] ของ Demo)
      'id' => 1, 'name' => 'System Admin', 'email' => 'admin@example.com',
      'account_type' => 'admin', 'avatar' => 'https://i.pravatar.cc/150?img=12',
    ];
  @endphp
  <script>
    window.LCD_CURRENT_USER = @json($clientUser);
  </script>

  {{--
    สคริปต์ของหน้า — ลำดับตาม Demo เดิม (mock-data → auth → permissions → page script)
    mock-data.js / permissions.js คือไฟล์ต้นฉบับ 100% (ยังใช้ localStorage เป็นแหล่งข้อมูล
    จนกว่าจะเชื่อม GET /api/v1/leads ตามแผนใน README ของ Demo)
    auth-bridge.js แทน auth.js เดิม เพราะการยืนยันตัวตนย้ายไปเป็น middleware ฝั่ง Server แล้ว
  --}}
  <script src="{{ asset('assets/js/mock-data.js') }}"></script>
  <script src="{{ asset('assets/js/auth-bridge.js') }}"></script>
  <script src="{{ asset('assets/js/permissions.js') }}"></script>
  <script src="{{ asset('assets/js/leads.js') }}"></script>
@endpush
