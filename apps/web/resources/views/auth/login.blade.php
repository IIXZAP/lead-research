<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>เข้าสู่ระบบ — {{ config('app.name') }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = { darkMode: 'class', theme: { extend: {
    fontFamily: { sans: ['Inter', 'Noto Sans Thai', 'sans-serif'] },
    colors: { brand: { 50:'#eef2ff',100:'#e0e7ff',500:'#6366f1',600:'#4f46e5',700:'#4338ca' } }
  } } };
</script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-slate-50 text-slate-800">

  <div class="min-h-screen grid lg:grid-cols-2">

    {{-- ฝั่งซ้าย: แบรนด์ (เหมือน ui/login.html) --}}
    <div class="hidden lg:flex flex-col justify-between bg-gradient-to-br from-indigo-600 to-indigo-700 text-white p-12">
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-lg bg-white/15 flex items-center justify-center">
          <i data-lucide="radar" class="w-5 h-5 text-white"></i>
        </div>
        <span class="font-semibold">{{ config('app.name') }}</span>
      </div>
      <div>
        <h1 class="text-3xl font-bold leading-tight">ค้นหา Lead ลูกค้าธุรกิจ<br />ได้เร็วกว่าเดิม</h1>
        <p class="mt-3 text-indigo-100 max-w-sm">สร้าง Campaign กำหนดพื้นที่และเงื่อนไข แล้วปล่อยให้ระบบค้นหาและวิเคราะห์ Lead ให้อัตโนมัติ</p>
      </div>
      <p class="text-xs text-indigo-200">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>

    {{-- ฝั่งขวา: ฟอร์ม Login จริง ผูกกับ POST /login --}}
    <div class="p-8 sm:p-12 flex flex-col justify-center">
      <div class="lg:hidden flex items-center gap-2.5 mb-8">
        <div class="w-9 h-9 rounded-lg bg-indigo-600 flex items-center justify-center">
          <i data-lucide="radar" class="w-5 h-5 text-white"></i>
        </div>
        <span class="font-semibold text-slate-800">{{ config('app.name') }}</span>
      </div>

      <h2 class="text-2xl font-bold text-slate-800">เข้าสู่ระบบ</h2>
      <p class="text-sm text-slate-500 mt-1 mb-6">กรอกข้อมูลบัญชีของคุณเพื่อเข้าใช้งาน Dashboard</p>

      @if ($errors->any())
        <div class="mb-4 flex items-start gap-2.5 rounded-lg bg-rose-50 text-rose-700 text-sm px-4 py-3 ring-1 ring-rose-100">
          <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 shrink-0"></i>
          <span>{{ $errors->first() }}</span>
        </div>
      @endif

      <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
          <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">อีเมล</label>
          <div class="relative">
            <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" autofocus
                   placeholder="you@example.com"
                   class="w-full pl-10 pr-3 py-2.5 text-sm rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none transition @error('email') border-rose-400 @enderror" />
          </div>
          @error('email')
            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
          @enderror
        </div>

        <div>
          <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">รหัสผ่าน</label>
          <div class="relative">
            <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <input id="password" name="password" type="password" autocomplete="current-password" placeholder="••••••••"
                   class="w-full pl-10 pr-10 py-2.5 text-sm rounded-lg border border-slate-200 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none transition @error('password') border-rose-400 @enderror" />
            <button type="button" id="toggle-password" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" aria-label="แสดง/ซ่อนรหัสผ่าน">
              <i data-lucide="eye" class="w-4 h-4"></i>
            </button>
          </div>
          @error('password')
            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
          @enderror
        </div>

        <div class="flex items-center justify-between text-sm">
          <label class="flex items-center gap-2 text-slate-600 cursor-pointer select-none">
            <input name="remember" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
            จดจำฉัน
          </label>
        </div>

        <button type="submit"
                class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 rounded-lg transition">
          เข้าสู่ระบบ
        </button>
      </form>
    </div>
  </div>

  <script>
    if (window.lucide) lucide.createIcons();
    const pw = document.getElementById('password');
    const toggle = document.getElementById('toggle-password');
    toggle?.addEventListener('click', () => {
      const show = pw.type === 'password';
      pw.type = show ? 'text' : 'password';
      toggle.querySelector('i').setAttribute('data-lucide', show ? 'eye-off' : 'eye');
      if (window.lucide) lucide.createIcons();
    });
  </script>
</body>
</html>
