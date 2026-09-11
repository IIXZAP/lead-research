@props(['title' => ''])

@php
    $accountTypeLabels = [
        'admin' => 'ผู้ดูแลระบบ',
        'sales' => 'พนักงานขาย',
        'viewer' => 'ผู้เข้าชม',
    ];
    $user = auth()->user();
    $roleValue = $user?->account_type?->value ?? 'viewer';
@endphp

<header id="topbar" class="h-16 sticky top-0 z-20 bg-white/80 backdrop-blur-md border-b border-slate-100 flex items-center gap-3 px-4 sm:px-6">
    <button id="sidebar-collapse-btn" class="hidden lg:inline-flex text-slate-400 hover:text-slate-600">
        <i data-lucide="panel-left" class="w-5 h-5"></i>
    </button>

    <h1 class="font-semibold text-slate-800 truncate">{{ $title }}</h1>

    <div class="ml-auto flex items-center gap-3">
        <button class="text-slate-400 hover:text-slate-600 relative">
            <i data-lucide="bell" class="w-5 h-5"></i>
        </button>

        <div class="relative">
            <button id="topbar-user-btn" class="flex items-center gap-2 pl-1 pr-1.5 sm:pr-2 py-1 rounded-lg hover:bg-slate-100 transition-colors">
                <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-semibold ring-2 ring-slate-100">
                    {{ $user ? mb_substr($user->name, 0, 1) : '?' }}
                </span>
                <span class="hidden sm:block text-left">
                    <span class="block text-sm font-semibold text-slate-800 leading-tight">{{ $user->name ?? '-' }}</span>
                    <span class="block text-[11px] text-slate-400 leading-tight mt-0.5">{{ $accountTypeLabels[$roleValue] ?? $roleValue }}</span>
                </span>
                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 hidden sm:block"></i>
            </button>

            <div id="topbar-user-dropdown" class="hidden absolute right-0 mt-2.5 w-56 bg-white rounded-2xl shadow-xl ring-1 ring-slate-100 overflow-hidden py-1.5 origin-top-right">
                <div class="px-4 py-3 border-b border-slate-100">
                    <p class="text-sm font-semibold text-slate-800 truncate">{{ $user->name ?? '-' }}</p>
                    <p class="text-xs text-slate-400 truncate mt-0.5">{{ $user->email ?? '-' }}</p>
                    <span class="inline-block mt-1.5 text-[10px] font-medium px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600">
                        {{ $accountTypeLabels[$roleValue] ?? $roleValue }}
                    </span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 transition-colors">
                        <i data-lucide="log-out" class="w-4 h-4"></i> ออกจากระบบ
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

@push('scripts')
<script>
    (function () {
        const btn = document.getElementById('topbar-user-btn');
        const dropdown = document.getElementById('topbar-user-dropdown');
        if (!btn || !dropdown) return;
        btn.addEventListener('click', () => dropdown.classList.toggle('hidden'));
        document.addEventListener('click', (e) => {
            if (!btn.contains(e.target) && !dropdown.contains(e.target)) dropdown.classList.add('hidden');
        });
    })();
</script>
@endpush
