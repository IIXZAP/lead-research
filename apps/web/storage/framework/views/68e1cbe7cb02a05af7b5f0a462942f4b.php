<?php $__env->startSection('content'); ?>
    <h1 class="text-2xl font-display font-semibold mb-6">Dashboard</h1>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card p-5">
            <p class="text-sm text-ink-muted">Campaign ทั้งหมด</p>
            <p class="text-3xl font-mono font-semibold mt-1"><?php echo e($stats['campaign_count']); ?></p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-ink-muted">Lead ทั้งหมด</p>
            <p class="text-3xl font-mono font-semibold mt-1"><?php echo e($stats['lead_count']); ?></p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-ink-muted">Lead ใหม่</p>
            <p class="text-3xl font-mono font-semibold mt-1 text-brand"><?php echo e($stats['new_lead_count']); ?></p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-ink-muted">Lead ที่ผ่านการคัดกรอง</p>
            <p class="text-3xl font-mono font-semibold mt-1"><?php echo e($stats['qualified_lead_count']); ?></p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-ink-muted">Lead ที่ไม่มีเว็บไซต์</p>
            <p class="text-3xl font-mono font-semibold mt-1"><?php echo e($stats['leads_without_website_count']); ?></p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-ink-muted">เว็บไซต์มีปัญหาระดับสูง</p>
            <p class="text-3xl font-mono font-semibold mt-1 text-red-600"><?php echo e($stats['high_severity_issue_count']); ?></p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-ink-muted">API credits ที่ใช้</p>
            <p class="text-3xl font-mono font-semibold mt-1"><?php echo e($stats['credits_used']); ?></p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-ink-muted">Research Job กำลังทำงาน</p>
            <p class="text-3xl font-mono font-semibold mt-1 text-brand"><?php echo e($stats['running_job_count']); ?></p>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/dashboard.blade.php ENDPATH**/ ?>