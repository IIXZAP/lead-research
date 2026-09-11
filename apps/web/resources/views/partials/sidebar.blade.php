{{--
  partials/sidebar.blade.php
  แปลงจาก assets/js/sidebar.js (renderSidebar) → render ฝั่ง Server
  DOM structure / class name / id คงเดิมทั้งหมด — JS เหลือเฉพาะ interaction
  (collapse, mobile drawer, logout) ใน public/assets/js/sidebar.js
--}}
@php
  /** @var \App\Models\User|object $user */
  $user = auth()->user() ?? (object) [
    // Fallback สำหรับ preview ก่อนต่อระบบ Auth จริง (ตรงกับ MOCK_USERS[0] เดิม)
    'name' => 'System Admin',
    'email' => 'admin@example.com',
    'account_type' => 'admin',
    'avatar' => 'https://i.pravatar.cc/150?img=12',
  ];

  $accountTypeLabel = match ($user->account_type ?? null) {
    'admin' => 'ผู้ดูแลระบบ',
    'manager' => 'ผู้จัดการฝ่ายขาย',
    'sale' => 'พนักงานขาย',
    default => $user->account_type ?? '',
  };

  /**
   * แปลงชื่อ route → URL อย่างปลอดภัย:
   * - ไม่มี route ชื่อนี้ → ใช้ fallback path
   * - มี route แต่ generate ไม่ได้ (เช่น URI ต้องการ parameter อย่าง campaigns/{campaign}/leads)
   *   → fallback เช่นกัน แทนที่จะโยน UrlGenerationException ทำให้ทั้งหน้าล่ม
   * เมนู "Leads" ใน Sidebar คือ Lead Directory รวมทุก Campaign จึงต้องเป็น route แบบไม่มี parameter
   */
  $navUrl = function (string $name, string $fallback) {
      if (! \Illuminate\Support\Facades\Route::has($name)) {
          return url($fallback);
      }
      try {
          return route($name);
      } catch (\Throwable $e) {
          return url($fallback);
      }
  };

  // เมนูหลัก (ตรงกับ NAV_ITEMS ใน sidebar.js) — เปลี่ยน href เป็น named route เมื่อมี
  $navItems = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'dashboard',       'fallback' => '/dashboard'],
    ['key' => 'campaigns', 'label' => 'Campaigns', 'icon' => 'target',            'route' => 'campaigns.index', 'fallback' => '/campaigns'],
    ['key' => 'leads',     'label' => 'Leads',     'icon' => 'users',             'route' => 'leads.index',     'fallback' => '/leads'],
  ];

  // เมนูอนาคต (ตรงกับ NAV_ITEMS_FUTURE) — แสดง disabled พร้อม badge "Soon"
  $navItemsFuture = [
    ['key' => 'companies', 'label' => 'Companies', 'icon' => 'building-2'],
    ['key' => 'reports',   'label' => 'Reports',   'icon' => 'bar-chart-3'],
    ['key' => 'users',     'label' => 'Users',     'icon' => 'shield-user', 'adminOnly' => true],
    ['key' => 'settings',  'label' => 'Settings',  'icon' => 'settings'],
  ];

  $collapsed = false; // สถานะจริงอยู่ใน localStorage ฝั่ง Client — script ท้าย partial จะ apply ก่อน paint
@endphp

<aside id="sidebar">
  <div id="sidebar-inner" class="relative h-screen sticky top-0 flex flex-col bg-white border-r border-slate-200/80 shadow-[1px_0_0_0_rgba(0,0,0,0.02)] transition-all duration-200 w-64">

    <!-- Logo -->
    <div class="h-16 flex items-center gap-2.5 px-4 border-b border-slate-100 shrink-0">
      <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center shrink-0 shadow-sm shadow-indigo-200">
        <i data-lucide="radar" class="w-[18px] h-[18px] text-white"></i>
      </div>
      <div class="nav-label min-w-0">
        <span class="block font-bold text-slate-800 tracking-tight truncate leading-tight">Lead Campaign</span>
        <span class="block text-[11px] text-slate-400 truncate leading-tight">Business Lead Finder</span>
      </div>
    </div>

    <!-- Nav -->
    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-5 space-y-1">
      <div class="nav-label px-3 pb-2 text-[10px] font-bold uppercase tracking-widest text-slate-300">เมนูหลัก</div>

      @foreach ($navItems as $item)
        @php
          $active = $item['key'] === ($activePage ?? '');
          $href = $navUrl($item['route'], $item['fallback']);
        @endphp
        <a href="{{ $href }}"
           class="nav-item group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-150
                  {{ $active
                    ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-md shadow-indigo-200'
                    : 'text-slate-500 hover:bg-slate-100/80 hover:text-slate-900' }}"
           data-nav-key="{{ $item['key'] }}" title="{{ $item['label'] }}">
          <span class="flex items-center justify-center w-6 h-6 shrink-0 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-indigo-600' }} transition-colors">
            <i data-lucide="{{ $item['icon'] }}" class="w-[18px] h-[18px]"></i>
          </span>
          <span class="nav-label truncate">{{ $item['label'] }}</span>
        </a>
      @endforeach

      {{-- <div class="nav-label pt-5 pb-2 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-300">เร็ว ๆ นี้</div> --}}

      {{-- @foreach ($navItemsFuture as $item)
        @continue(($item['adminOnly'] ?? false) && ($user->account_type ?? null) !== 'admin')
        <div class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300 cursor-not-allowed select-none"
             title="{{ $item['label'] }} · เร็ว ๆ นี้">
          <span class="flex items-center justify-center w-6 h-6 shrink-0 text-slate-300">
            <i data-lucide="{{ $item['icon'] }}" class="w-[18px] h-[18px]"></i>
          </span>
          <span class="nav-label truncate">{{ $item['label'] }}</span>
          <span class="nav-label ml-auto text-[9px] font-semibold uppercase tracking-wider bg-slate-100 text-slate-400 rounded-full px-2 py-0.5">Soon</span>
        </div>
      @endforeach --}}
    </nav>

    <!-- User card -->
    <div class="border-t border-slate-100 p-3 shrink-0">
      <div class="flex items-center gap-2.5 rounded-xl px-2 py-2 hover:bg-slate-50 transition-colors">
        <img src="{{ $user->avatar ?? 'https://i.pravatar.cc/150?img=12' }}" alt="{{ $user->name }}" class="w-9 h-9 rounded-full object-cover shrink-0 ring-2 ring-indigo-50 shadow-sm" />
        <div class="nav-label min-w-0 flex-1">
          <p class="text-sm font-semibold text-slate-800 truncate leading-tight">{{ $user->name }}</p>
          <p class="text-[11px] text-slate-400 truncate leading-tight mt-0.5">{{ $accountTypeLabel }}</p>
        </div>
        <button id="sidebar-logout-btn" class="nav-label text-slate-300 hover:text-rose-500 hover:bg-rose-50 rounded-lg p-1.5 shrink-0 transition-colors" title="ออกจากระบบ" aria-label="ออกจากระบบ">
          <i data-lucide="log-out" class="w-4 h-4"></i>
        </button>
      </div>
      <button id="sidebar-collapse-btn"
              class="hidden lg:flex mt-1.5 w-full items-center justify-center gap-2 rounded-lg px-2 py-1.5 text-[11px] font-medium text-slate-400 hover:bg-slate-50 hover:text-indigo-600 transition-colors">
        <i data-lucide="panel-left-close" class="w-3.5 h-3.5"></i>
        <span class="nav-label">ย่อเมนู</span>
      </button>
    </div>

    <!-- Floating collapse toggle (desktop) -->
    <button id="sidebar-edge-toggle"
            class="hidden lg:flex absolute -right-3 top-[70px] w-6 h-6 rounded-full bg-white border border-slate-200 shadow-md items-center justify-center text-slate-400 hover:text-indigo-600 hover:border-indigo-200 transition-colors z-10"
            title="ย่อ/ขยายเมนู" aria-label="ย่อ/ขยายเมนู">
      <i data-lucide="chevron-left" class="w-3.5 h-3.5 transition-transform"></i>
    </button>
  </div>

  <!-- Mobile overlay -->
  <div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px] z-30 lg:hidden hidden"></div>
</aside>

{{-- Apply สถานะย่อเมนูจาก localStorage ทันทีก่อน paint (แทนการ apply ตอน render ใน JS เดิม) --}}
<script>
  (function () {
    if (localStorage.getItem('lcd_sidebar_collapsed') !== '1') return;
    var inner = document.getElementById('sidebar-inner');
    inner.classList.remove('w-64'); inner.classList.add('w-[76px]');
    document.querySelectorAll('#sidebar .nav-label').forEach(function (el) { el.classList.add('hidden'); });
    var collapseIcon = document.querySelector('#sidebar-collapse-btn i');
    if (collapseIcon) collapseIcon.setAttribute('data-lucide', 'panel-left-open');
    var edgeIcon = document.querySelector('#sidebar-edge-toggle i');
    if (edgeIcon) edgeIcon.classList.add('rotate-180');
  })();
</script>
