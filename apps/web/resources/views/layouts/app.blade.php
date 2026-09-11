@php
  // หน้า child กำหนด active page ได้ 2 แบบ: ส่งตัวแปร $activePage หรือประกาศ @section('page', '...')
  $activePage = $activePage ?? trim($__env->yieldContent('page', 'dashboard'));
@endphp
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<title>@yield('title', 'Lead Campaign Dashboard') — Lead Campaign Dashboard</title>

{{-- Tailwind Play CDN + config เดิมจาก Demo (ทุกหน้าใช้ config ก้อนเดียวกัน) --}}
{{-- TODO(ขั้นถัดไป): ย้ายเข้า Vite + tailwind.config.js เมื่อ setup build pipeline --}}
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = { darkMode: 'class', theme: { extend: {
    fontFamily: { sans: ['Inter', 'Noto Sans Thai', 'sans-serif'] },
    colors: { brand: { 50:'#eef2ff',100:'#e0e7ff',500:'#6366f1',600:'#4f46e5',700:'#4338ca' } }
  } } };
</script>

{{-- ป้องกัน Dark mode กระพริบ: ใส่ class ก่อน render (พฤติกรรมเทียบเท่า applyStoredDarkMode เดิม) --}}
<script>
  if (localStorage.getItem('lcd_dark_mode') === '1') document.documentElement.classList.add('dark');
</script>

{{--
  Fonts: Inter + Noto Sans Thai (weight ชุดเดียวกับที่ Demo ระบุใน app.css เดิมเป๊ะ)
  ยกออกมาโหลดใน <head> โดยตรง แทนการพึ่ง @import ข้างใน app.css ซึ่งไม่เสถียร
  (fetch ซ้อน + render-blocking) เป็นสาเหตุที่ฟอนต์ไม่โหลด → ตกไปใช้ฟอนต์ระบบที่ใหญ่กว่า
  ทำให้ตัวอักษรใหญ่และระยะห่างบวมกว่า Demo
--}}
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" />

<link rel="stylesheet" href="{{ asset('assets/css/app.css') }}" />
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

{{-- สำหรับหน้าเฉพาะที่ต้องโหลด lib เพิ่ม เช่น dashboard push ApexCharts เข้ามาที่นี่ --}}
@stack('head')
</head>
<body data-page="{{ $activePage ?? 'dashboard' }}" class="bg-slate-50 text-slate-800">

  <div class="flex min-h-screen">

    @include('partials.sidebar', ['activePage' => $activePage ?? 'dashboard'])

    <div class="flex-1 min-w-0 flex flex-col">

      @include('partials.topbar', ['activePage' => $activePage ?? 'dashboard'])

      {{--
        main-class ให้แต่ละหน้ากำหนดเองเพื่อคง layout เดิมเป๊ะ:
        - dashboard:        space-y-6 max-w-[1600px]
        - campaigns/leads:  space-y-5 max-w-[1600px]
        - campaign-create:  max-w-4xl (ไม่มี space-y)
        - business-info:    space-y-5 max-w-[1400px]
      --}}
      <main id="page-content" class="flex-1 p-4 sm:p-6 @yield('main-class', 'space-y-6 max-w-[1600px]') w-full mx-auto">
        @yield('content')
      </main>

      @include('partials.footer')
    </div>
  </div>

  {{-- ฟอร์ม Logout กลาง: ปุ่มใน sidebar/topbar จะ submit ฟอร์มนี้ (คง DOM ของปุ่มเดิมไว้ทุกอย่าง) --}}
  <form id="logout-form" method="POST" action="{{ Route::has('logout') ? route('logout') : '#' }}" class="hidden">
    @csrf
  </form>

  {{-- Shared scripts — ลำดับเดิมตาม Demo (mock-data/auth/permissions ถูกถอดออก เพราะย้ายไปฝั่ง Server) --}}
  <script src="{{ asset('assets/js/utils.js') }}"></script>
  <script src="{{ asset('assets/js/toast.js') }}"></script>
  <script src="{{ asset('assets/js/app.js') }}"></script>
  <script src="{{ asset('assets/js/sidebar.js') }}"></script>
  <script src="{{ asset('assets/js/topbar.js') }}"></script>

  @stack('scripts')

  <script>
    // Demo เดิมเรียก lucide.createIcons() ภายใน render function ของแต่ละส่วน
    // เมื่อ markup ย้ายมา render ฝั่ง Server จึงเรียกรวมครั้งเดียวที่นี่
    if (window.lucide) lucide.createIcons();
  </script>
</body>
</html>
