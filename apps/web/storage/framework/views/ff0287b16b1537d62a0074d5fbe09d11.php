<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เข้าสู่ระบบ · <?php echo e(config('app.name')); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="min-h-screen flex items-center justify-center relative overflow-hidden">
    <!-- Ambient radar rings behind the card — quiet, not distracting -->
    <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
        <div class="h-[600px] w-[600px] rounded-full border border-brand/10"></div>
        <div class="absolute h-[420px] w-[420px] rounded-full border border-brand/10"></div>
        <div class="absolute h-[240px] w-[240px] rounded-full border border-brand/10"></div>
    </div>

    <div class="relative w-full max-w-sm animate-fade-in-up">
        <div class="flex items-center gap-2 justify-center mb-6">
            <span class="relative flex h-8 w-8 items-center justify-center rounded-lg bg-brand text-white">
                <span class="absolute inline-flex h-full w-full rounded-lg bg-brand/60 animate-pulse-ring"></span>
                <svg class="relative h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <span class="font-display text-lg font-semibold tracking-tight"><?php echo e(config('app.name')); ?></span>
        </div>

        <div class="card p-7">
            <?php if($errors->any()): ?>
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?php echo e($errors->first()); ?>

                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('login')); ?>" class="space-y-4">
                <?php echo csrf_field(); ?>
                <div>
                    <label for="email" class="block text-sm font-medium text-ink-muted mb-1.5">อีเมล</label>
                    <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus
                           class="input" placeholder="you@company.com">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-ink-muted mb-1.5">รหัสผ่าน</label>
                    <input id="password" type="password" name="password" required class="input" placeholder="••••••••">
                </div>
                <button type="submit" class="btn-primary w-full py-2.5">
                    เข้าสู่ระบบ
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-ink-muted mt-6">Lead Research Platform</p>
    </div>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/auth/login.blade.php ENDPATH**/ ?>