<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e(config('app.name')); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body>
    <?php if(auth()->guard()->check()): ?>
    <nav class="sticky top-0 z-10 bg-surface/80 backdrop-blur-md border-b border-line">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="<?php echo e(route('dashboard')); ?>" class="flex items-center gap-2 group">
                    <span class="relative flex h-6 w-6 items-center justify-center rounded-md bg-brand text-white">
                        <span class="absolute inline-flex h-full w-full rounded-md bg-brand/60 animate-pulse-ring"></span>
                        <svg class="relative h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </span>
                    <span class="font-display font-semibold tracking-tight"><?php echo e(config('app.name')); ?></span>
                </a>
                <div class="flex items-center gap-6">
                    <a href="<?php echo e(route('dashboard')); ?>" class="nav-link">Dashboard</a>
                    <a href="<?php echo e(route('campaigns.index')); ?>" class="nav-link">Campaigns</a>
                </div>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <span class="text-ink-muted"><?php echo e(auth()->user()->name); ?> <span class="text-line">·</span> <span class="font-mono text-xs uppercase tracking-wide"><?php echo e(auth()->user()->account_type->value); ?></span></span>
                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="nav-link">Sign out</button>
                </form>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <main class="max-w-6xl mx-auto px-4 py-8 animate-fade-in-up">
        <?php if(session('status')): ?>
            <div class="mb-4 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <svg class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>

        <?php echo $__env->yieldContent('content'); ?>
    </main>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/layouts/app.blade.php ENDPATH**/ ?>