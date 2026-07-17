<?php $__env->startSection('content'); ?>
    <h1 class="text-2xl font-display font-semibold mb-6">New Campaign</h1>

    <form method="POST" action="<?php echo e(route('campaigns.store')); ?>" class="card p-6 space-y-4 max-w-xl">
        <?php echo csrf_field(); ?>
        <?php echo $__env->make('campaigns._form', ['campaign' => null], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <button type="submit" class="btn-primary">สร้างแคมเปญ</button>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/campaigns/create.blade.php ENDPATH**/ ?>