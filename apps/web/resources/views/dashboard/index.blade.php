{{--
  dashboard/index.blade.php
  แปลงจาก dashboard.html (Demo) — ใช้ layouts.app (Sidebar/Topbar ไม่เขียนซ้ำ)
  Card / Recent Campaigns / Top Leads : render ฝั่ง Server ด้วย @foreach / @forelse
  Chart 2 ตัว (Area + Donut) : ยังใช้ ApexCharts เดิม โดยรับข้อมูลจาก Controller ผ่าน @json
  ข้อมูลทั้งหมดมาจาก DashboardController (mock PHP array — ยังไม่เชื่อม DB)
--}}
@extends('layouts.app')

@section('page', 'dashboard')
@section('title', 'Dashboard')
@section('main-class', 'space-y-6 max-w-[1600px]')

{{-- Chart library เดียวที่หน้านี้ใช้ (ยืนยันจาก dashboard.html เดิม) --}}
@push('head')
  <script src="https://cdn.jsdelivr.net/npm/apexcharts@3"></script>
@endpush

@section('content')

  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold text-slate-800">ภาพรวมระบบค้นหา Lead</h1>
      <p id="dashboard-scope-desc" class="text-sm text-slate-500 mt-0.5">{{ $scopeDesc }}</p>
    </div>
    {{-- ซ่อน/แสดงปุ่มตามสิทธิ์ (UI) — Route ฝั่ง Server ต้องมี middleware ป้องกันซ้ำ --}}
    @if ($canCreateCampaign)
      <a href="{{ $links['create'] }}" id="dashboard-create-btn" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg px-3.5 py-2">
        <i data-lucide="plus" class="w-4 h-4"></i> สร้าง Campaign
      </a>
    @endif
  </div>

  <!-- Summary Cards -->
  <div id="summary-cards" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach ($summaryCards as $card)
      <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4 hover:shadow-md transition-shadow">
        <div class="w-9 h-9 rounded-lg {{ $card['colorClass'] }} flex items-center justify-center mb-3">
          <i data-lucide="{{ $card['icon'] }}" class="w-5 h-5"></i>
        </div>
        <p class="text-2xl font-bold text-slate-800">{{ number_format($card['value']) }}</p>
        <div class="flex items-center justify-between mt-1">
          <p class="text-xs text-slate-500">{{ $card['label'] }}</p>
          <span class="text-xs font-medium {{ $card['up'] ? 'text-emerald-600' : 'text-rose-500' }} flex items-center gap-0.5 shrink-0">
            <i data-lucide="{{ $card['up'] ? 'arrow-up-right' : 'arrow-down-right' }}" class="w-3 h-3"></i>{{ $card['trend'] }}
          </span>
        </div>
      </div>
    @endforeach
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="card-surface xl:col-span-2 bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-5">
      <h2 class="font-semibold text-slate-800 mb-4">Lead ที่ค้นพบรายวัน (14 วันล่าสุด)</h2>
      <div id="chart-leads-daily" class="min-h-[280px]"></div>
    </div>
    <div class="card-surface bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-5">
      <h2 class="font-semibold text-slate-800 mb-4">Campaign แยกตามสถานะ</h2>
      <div id="chart-campaign-status" class="min-h-[280px]"></div>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="card-surface bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-slate-800">Campaign ล่าสุด</h2>
        <a href="{{ $links['campaigns'] }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">ดูทั้งหมด</a>
      </div>
      <div id="recent-campaigns-list" class="divide-y divide-slate-50">
        @forelse ($recentCampaigns as $c)
          <a href="{{ $c['url'] }}" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0 hover:bg-slate-50 -mx-2 px-2 rounded-lg transition-colors">
            <span class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
              <i data-lucide="target" class="w-4 h-4"></i>
            </span>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-slate-700 truncate">{{ $c['title'] }}</p>
              <p class="text-xs text-slate-400 truncate">{{ $c['province'] }} · {{ $c['leads_found'] }}/{{ $c['maximum_leads'] }} Lead · {{ $c['time_ago'] }}</p>
            </div>
            <span class="text-xs font-medium px-2 py-0.5 rounded-full ring-1 {{ $c['status_color'] }} whitespace-nowrap">{{ $c['status_label'] }}</span>
          </a>
        @empty
          <div class="flex flex-col items-center justify-center py-14 text-center">
            <i data-lucide="inbox" class="w-9 h-9 text-slate-300 mb-3"></i>
            <p class="text-slate-500 font-medium">ยังไม่มี Campaign</p>
          </div>
        @endforelse
      </div>
    </div>

    <div class="card-surface bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-slate-800">Lead คุณภาพสูงล่าสุด</h2>
        <a href="{{ $links['leads'] }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">ดูทั้งหมด</a>
      </div>
      <div id="top-leads-list" class="divide-y divide-slate-50">
        @forelse ($topLeads as $b)
          <a href="{{ $b['url'] }}" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0 hover:bg-slate-50 -mx-2 px-2 rounded-lg transition-colors">
            <span class="w-9 h-9 text-xs rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-semibold shrink-0">{{ $b['initials'] }}</span>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-slate-700 truncate">{{ $b['company_name_th'] }}</p>
              <p class="text-xs text-slate-400 truncate">{{ $b['business_type'] }} · Score {{ $b['score'] }}</p>
            </div>
            <span class="text-xs font-semibold text-emerald-600 shrink-0">{{ $b['quality'] }}</span>
          </a>
        @empty
          <div class="flex flex-col items-center justify-center py-14 text-center">
            <i data-lucide="inbox" class="w-9 h-9 text-slate-300 mb-3"></i>
            <p class="text-slate-500 font-medium">ยังไม่มี Lead</p>
          </div>
        @endforelse
      </div>
    </div>
  </div>

@endsection

@push('scripts')
  {{-- ป้อนข้อมูลกราฟจากฝั่ง Server ให้ JS (config ApexCharts เดิมทั้งหมดอยู่ใน dashboard.js) --}}
  <script>
    window.LCD_DASHBOARD = {
      daily: @json($dailyChart),
      status: @json($statusChart),
    };
  </script>
  <script src="{{ asset('assets/js/dashboard.js') }}"></script>
@endpush
