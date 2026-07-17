<?php $__env->startSection('content'); ?>
    <?php ($latestAudit = $lead->audits->first()); ?>

    <a href="<?php echo e(route('leads.index', $lead->campaign)); ?>" class="text-sm text-ink-muted hover:text-brand transition-colors">&larr; Leads</a>

    <div class="flex items-center justify-between mt-2 mb-6">
        <h1 class="text-2xl font-display font-semibold"><?php echo e($lead->company_name); ?></h1>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('updateStatus', $lead)): ?>
            <form method="POST" action="<?php echo e(route('leads.update-status', $lead)); ?>" class="flex items-center gap-2">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>
                <select name="status" class="input !w-auto">
                    <?php $__currentLoopData = \App\Enums\LeadStatus::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($status->value); ?>" <?php if($lead->status === $status): echo 'selected'; endif; ?>><?php echo e($status->value); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <button type="submit" class="btn-primary">Update</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="card p-6">
            <h2 class="text-sm font-semibold text-ink-muted mb-4">Contact info</h2>
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between"><dt class="text-ink-muted">Phone</dt><dd class="font-mono"><?php echo e($lead->phone ?? '—'); ?></dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Website</dt>
                    <dd><?php if($lead->website_url): ?><a href="<?php echo e($lead->website_url); ?>" target="_blank" rel="noopener" class="text-brand hover:underline"><?php echo e($lead->website_url); ?></a><?php else: ?> — <?php endif; ?></dd>
                </div>
                <div class="flex justify-between"><dt class="text-ink-muted">Address</dt><dd class="text-right"><?php echo e($lead->address ?? '—'); ?></dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Province</dt><dd><?php echo e($lead->province ?? '—'); ?></dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Business type</dt><dd><?php echo e($lead->business_type ?? '—'); ?></dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Source</dt><dd class="font-mono text-xs"><?php echo e($lead->source ?? '—'); ?></dd></div>
            </dl>
        </div>

        <div class="card p-6">
            <h2 class="text-sm font-semibold text-ink-muted mb-4">Website audit</h2>
            <?php if($latestAudit): ?>
                <div class="flex items-center gap-3 mb-4">
                    <span class="badge font-mono text-base px-3 py-1
                        <?php echo e(($latestAudit->audit_score ?? 0) < 40 ? 'bg-red-100 text-red-700' : (($latestAudit->audit_score ?? 0) < 70 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700')); ?>">
                        <?php echo e($latestAudit->audit_score ?? '—'); ?>

                    </span>
                    <?php if($latestAudit->confidence_score !== null): ?>
                        <span class="text-xs text-ink-muted">confidence <?php echo e(number_format($latestAudit->confidence_score * 100)); ?>%</span>
                    <?php endif; ?>
                </div>
                <?php if(!empty($latestAudit->issues)): ?>
                    <ul class="space-y-2.5 text-sm">
                        <?php $__currentLoopData = $latestAudit->issues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $issue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class="flex items-start gap-2">
                                <span class="badge mt-0.5
                                    <?php echo e(in_array($issue['severity'], ['critical','high']) ? 'bg-red-100 text-red-700' : ($issue['severity'] === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-ink-muted')); ?>">
                                    <?php echo e($issue['severity']); ?>

                                </span>
                                <span><?php echo e($issue['message_th']); ?></span>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php else: ?>
                    <p class="text-sm text-ink-muted">ยังไม่พบปัญหาที่ชัดเจนจากการตรวจสอบเบื้องต้น</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-sm text-ink-muted">ยังไม่มีข้อมูลการตรวจสอบเว็บไซต์</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card p-6 mt-6">
        <h2 class="text-sm font-semibold text-ink-muted mb-4">Notes</h2>

        <ul class="space-y-3 mb-4">
            <?php $__empty_1 = true; $__currentLoopData = $lead->notes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $note): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <li class="text-sm border-b border-line pb-3 last:border-0">
                    <p><?php echo e($note->note); ?></p>
                    <p class="text-xs text-ink-muted mt-1"><?php echo e($note->user->name); ?> · <?php echo e($note->created_at->format('Y-m-d H:i')); ?></p>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <li class="text-sm text-ink-muted">ยังไม่มีโน้ต</li>
            <?php endif; ?>
        </ul>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('addNote', $lead)): ?>
            <form method="POST" action="<?php echo e(route('leads.notes.store', $lead)); ?>" class="flex gap-2">
                <?php echo csrf_field(); ?>
                <input type="text" name="note" required maxlength="2000" placeholder="เพิ่มโน้ต..." class="input flex-1">
                <button type="submit" class="btn-primary">Add</button>
            </form>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/leads/show.blade.php ENDPATH**/ ?>