<?php $__env->startSection('content'); ?>
    <?php ($isRunning = in_array($campaign->status->value, ['queued', 'processing'])); ?>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-display font-semibold"><?php echo e($campaign->name); ?></h1>
            <p class="text-sm text-ink-muted mt-1">
                <span class="badge
                    <?php echo e(match(true) {
                        $campaign->status->value === 'completed' => 'bg-emerald-100 text-emerald-700',
                        in_array($campaign->status->value, ['processing','queued']) => 'bg-brand-light text-brand',
                        $campaign->status->value === 'failed' => 'bg-red-100 text-red-700',
                        $campaign->status->value === 'partially_completed' => 'bg-amber-100 text-amber-700',
                        default => 'bg-gray-100 text-gray-600',
                    }); ?>"><?php echo e($campaign->status->value); ?></span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('leads.index', $campaign)); ?>" class="btn-secondary">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                View Leads
            </a>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('startJob', $campaign)): ?>
                <?php if(in_array($campaign->status->value, ['draft', 'failed', 'cancelled', 'completed', 'partially_completed'])): ?>
                    <form method="POST" action="<?php echo e(route('campaigns.start', $campaign)); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn-primary">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            Start
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('cancelJob', $campaign)): ?>
                <?php if($isRunning): ?>
                    <form method="POST" action="<?php echo e(route('campaigns.cancel', $campaign)); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn-secondary text-amber-700 border-amber-200 hover:bg-amber-50">Cancel</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $campaign)): ?>
                <a href="<?php echo e(route('campaigns.edit', $campaign)); ?>" class="btn-ghost">Edit</a>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $campaign)): ?>
                <form method="POST" action="<?php echo e(route('campaigns.destroy', $campaign)); ?>" onsubmit="return confirm('ลบแคมเปญนี้?')">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn-danger">Delete</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="card p-6">
            <h2 class="text-sm font-semibold text-ink-muted mb-4">Search criteria</h2>
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between"><dt class="text-ink-muted">Keyword</dt><dd class="font-medium"><?php echo e($campaign->business_keyword); ?></dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Province</dt><dd class="font-medium"><?php echo e($campaign->province ?? '—'); ?></dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Radius</dt><dd class="font-mono"><?php echo e($campaign->radius_km); ?> km</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Maximum leads</dt><dd class="font-mono"><?php echo e($campaign->maximum_leads); ?></dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Owner</dt><dd class="font-medium"><?php echo e($campaign->user->name); ?></dd></div>
            </dl>
        </div>

        <div class="card p-6">
            <h2 class="text-sm font-semibold text-ink-muted mb-4">Job progress</h2>
            <?php if($campaign->researchJobs->isNotEmpty()): ?>
                <?php ($job = $campaign->researchJobs->first()); ?>
                <dl class="space-y-2.5 text-sm mb-4">
                    <div class="flex justify-between"><dt class="text-ink-muted">Stage</dt><dd class="font-medium"><?php echo e($job->current_stage ?? '—'); ?></dd></div>
                    <div class="flex justify-between"><dt class="text-ink-muted">Processed</dt><dd class="font-mono"><?php echo e($job->processed_items); ?> / <?php echo e($job->total_items); ?></dd></div>
                </dl>

                <div class="flex items-center justify-between text-xs text-ink-muted mb-1.5">
                    <span>Progress</span>
                    <span class="font-mono"><?php echo e($job->progress_percent); ?>%</span>
                </div>
                <?php if($isRunning): ?>
                    <div class="scan-track h-2">
                        <div class="scan-sweep h-full"></div>
                        <div class="h-full bg-brand rounded-full transition-all duration-500" style="width: <?php echo e($job->progress_percent); ?>%"></div>
                    </div>
                <?php else: ?>
                    <div class="h-2 rounded-full bg-brand-light overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500 <?php echo e($campaign->status->value === 'failed' ? 'bg-red-500' : 'bg-brand'); ?>" style="width: <?php echo e($job->progress_percent); ?>%"></div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-sm text-ink-muted">ยังไม่มี research job ที่รันไว้</p>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/campaigns/show.blade.php ENDPATH**/ ?>