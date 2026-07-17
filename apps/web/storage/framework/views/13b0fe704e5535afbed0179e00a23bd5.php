<?php $__env->startSection('content'); ?>
    <h1 class="text-xl font-semibold mb-6">Edit Campaign</h1>

    <form method="POST" action="<?php echo e(route('campaigns.update', $campaign)); ?>" class="bg-white rounded-lg border border-gray-200 p-6 space-y-4 max-w-xl">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <?php echo $__env->make('campaigns._form', ['campaign' => $campaign], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <button type="submit" class="bg-gray-900 text-white text-sm rounded-md px-4 py-2">
            บันทึกการแก้ไข
        </button>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/campaigns/edit.blade.php ENDPATH**/ ?>