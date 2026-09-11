{{--
  dashboard.blade.php — หน้า "ตัวอย่างการใช้ layout" เท่านั้น
  เนื้อหา Dashboard จริง (Summary Cards, Charts ฯลฯ) จะแปลงในขั้นถัดไปตามที่ตกลง
--}}
@extends('layouts.app')

@section('page', 'dashboard')
@section('title', 'Dashboard')
@section('main-class', 'space-y-6 max-w-[1600px]')

{{-- ตัวอย่าง: หน้า dashboard ต้องใช้ ApexCharts เพิ่ม ให้ push เข้า head แบบนี้ --}}
@push('head')
  <script src="https://cdn.jsdelivr.net/npm/apexcharts@3"></script>
@endpush

@section('content')
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
    <div>
      <h1 class="text-xl sm:text-2xl font-bold text-slate-800">ภาพรวมระบบค้นหา Lead</h1>
      <p class="text-sm text-slate-500 mt-0.5">Layout พร้อมใช้งานแล้ว — เนื้อหาหน้านี้จะถูกแปลงในขั้นถัดไป</p>
    </div>
  </div>

  <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-8 text-center">
    <i data-lucide="layout-template" class="w-10 h-10 text-slate-300 mx-auto mb-3"></i>
    <p class="text-slate-500 font-medium">พื้นที่เนื้อหา (page-content)</p>
    <p class="text-sm text-slate-400 mt-1">Sidebar, Topbar และ Footer ทำงานครบ: ย่อเมนู, Mobile drawer, Dropdown, Dark mode</p>
  </div>
@endsection

{{-- ตัวอย่างการ push script เฉพาะหน้า --}}
@push('scripts')
  <script>
    // ตัวอย่างการรับค่า search จาก topbar (แทน options.onSearch เดิม)
    // window.onTopbarSearch = (value) => console.log('search:', value);
  </script>
@endpush
