@props(['active' => ''])

@php
    $navItems = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'layout-grid', 'href' => route('dashboard')],
        ['key' => 'campaigns', 'label' => 'Campaigns', 'icon' => 'target', 'href' => route('campaigns.index')],
        ['key' => 'leads', 'label' => 'Leads', 'icon' => 'users', 'href' => route('leads.all')],
    ];

    $comingSoon = [
        ['label' => 'Companies', 'icon' => 'building-2'],
        ['label' => 'Reports', 'icon' => 'bar-chart-3'],
        ['label' => 'Users', 'icon' => 'shield-user'],
        ['label' => 'Settings', 'icon' => 'settings'],
    ];

    $user = auth()->user();
    $accountTypeLabels = ['admin' => 'ผู้ดูแลระบบ', 'sales' => 'พนักงานขาย', 'viewer' => 'ผู้เข้าชม'];
    $roleValue = $user?->account_type?->value ?? 'viewer';
@endphp

<aside id="sidebar" class="hidden lg:flex lg:flex-col w-64 shrink-0 bg-white border-r border-slate-100 h-screen sticky top-0 relative">

    {{-- ปุ่มย่อ/ขยายเมนู ลอยติดขอบขวาของ sidebar --}}
    <button id="sidebar-collapse-toggle"
            class="hidden lg:flex absolute -right-3 top-9 w-7 h-7 rounded-full bg-white ring-1 ring-slate-200 shadow-sm items-center justify-center text-slate-400 hover:text-indigo-600 z-10">
        <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
    </button>

    {{-- Logo --}}
    <div class="h-[72px] flex items-center gap-2.5 px-5">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center shrink-0">
            <i data-lucide="radar" class="w-5 h-5 text-white"></i>
        </div>
        <div class="min-w-0" data-sidebar-label>
            <p class="font-bold text-slate-800 leading-tight truncate">{{ config('app.name') }}</p>
            <p class="text-xs text-slate-400 leading-tight truncate">Business Lead Finder</p>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 pb-3 space-y-1">
        <p class="px-3 mt-1 mb-2 text-xs font-medium text-slate-400" data-sidebar-label>เมนูหลัก</p>

        @foreach ($navItems as $item)
            @php($isActive = $active === $item['key'])
            <a href="{{ $item['href'] }}"
               class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-150
                      {{ $isActive
                            ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-200'
                            : 'text-slate-600 hover:bg-slate-50' }}">
                <i data-lucide="{{ $item['icon'] }}" class="w-4.5 h-4.5 shrink-0 {{ $isActive ? 'text-white' : 'text-slate-400' }}"></i>
                <span data-sidebar-label>{{ $item['label'] }}</span>
            </a>
        @endforeach

        <p class="px-3 mt-6 mb-2 text-xs font-medium text-slate-400" data-sidebar-label>เร็วๆ นี้</p>

        @foreach ($comingSoon as $future)
            <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300 cursor-not-allowed">
                <i data-lucide="{{ $future['icon'] }}" class="w-4.5 h-4.5 shrink-0 text-slate-300"></i>
                <span class="flex-1 truncate" data-sidebar-label>{{ $future['label'] }}</span>
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-400 shrink-0" data-sidebar-label>SOON</span>
            </div>
        @endforeach
    </nav>

    {{-- User footer --}}
    <div class="border-t border-slate-100 p-3">
        <div class="flex items-center gap-2.5 px-1">
            <span class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-semibold shrink-0">
                {{ $user ? mb_substr($user->name, 0, 1) : '?' }}
            </span>
            <div class="min-w-0 flex-1" data-sidebar-label>
                <p class="text-sm font-semibold text-slate-800 truncate">{{ $user->name ?? '-' }}</p>
                <p class="text-xs text-slate-400 truncate">{{ $accountTypeLabels[$roleValue] ?? $roleValue }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" data-sidebar-label>
                @csrf
                <button type="submit" class="text-slate-400 hover:text-rose-600 shrink-0" title="ออกจากระบบ">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </button>
            </form>
        </div>

        <button id="sidebar-collapse-toggle-bottom" class="w-full mt-3 flex items-center justify-center gap-1.5 text-xs text-slate-400 hover:text-indigo-600 py-1.5" data-sidebar-label>
            <i data-lucide="panel-left-close" class="w-3.5 h-3.5"></i> ย่อเมนู
        </button>
    </div>
</aside>

{{-- Mobile drawer toggle button --}}
<button id="sidebar-mobile-toggle" class="lg:hidden fixed bottom-4 right-4 z-30 w-11 h-11 rounded-full bg-indigo-600 text-white shadow-lg flex items-center justify-center">
    <i data-lucide="menu" class="w-5 h-5"></i>
</button>

@once
@push('scripts')
<script>
    (function () {
        const SIDEBAR_COLLAPSE_KEY = 'lcd_sidebar_collapsed';
        const sidebar = document.getElementById('sidebar');
        const toggles = [
            document.getElementById('sidebar-collapse-toggle'),
            document.getElementById('sidebar-collapse-toggle-bottom'),
        ].filter(Boolean);

        function applyCollapsed(collapsed) {
            if (!sidebar) return;
            sidebar.classList.toggle('lg:w-64', !collapsed);
            sidebar.classList.toggle('lg:w-[76px]', collapsed);
            document.querySelectorAll('[data-sidebar-label]').forEach((el) => el.classList.toggle('hidden', collapsed));
            const chevron = document.querySelector('#sidebar-collapse-toggle i');
            if (chevron) chevron.setAttribute('data-lucide', collapsed ? 'chevron-right' : 'chevron-left');
            if (window.lucide) lucide.createIcons();
        }

        const initiallyCollapsed = localStorage.getItem(SIDEBAR_COLLAPSE_KEY) === '1';
        applyCollapsed(initiallyCollapsed);

        toggles.forEach((btn) => btn.addEventListener('click', () => {
            const collapsed = !(localStorage.getItem(SIDEBAR_COLLAPSE_KEY) === '1');
            localStorage.setItem(SIDEBAR_COLLAPSE_KEY, collapsed ? '1' : '0');
            applyCollapsed(collapsed);
        }));

        const mobileToggle = document.getElementById('sidebar-mobile-toggle');
        mobileToggle?.addEventListener('click', () => {
            sidebar?.classList.toggle('hidden');
            sidebar?.classList.toggle('flex');
            sidebar?.classList.toggle('fixed');
            sidebar?.classList.toggle('inset-y-0');
            sidebar?.classList.toggle('z-40');
        });
    })();
</script>
@endpush
@endonce
