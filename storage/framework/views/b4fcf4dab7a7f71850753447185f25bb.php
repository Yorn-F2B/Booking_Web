<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <?php echo $__env->make('admin.layouts.partials.head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

</head>

<body class="admin-page">
    <div class="d-none" aria-hidden="true">
        <?php if(session('success')): ?>
            <span data-admin-flash data-type="success"><?php echo e(session('success')); ?></span>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <span data-admin-flash data-type="error"><?php echo e(session('error')); ?></span>
        <?php endif; ?>
        <?php if(session('warning')): ?>
            <span data-admin-flash data-type="warning"><?php echo e(session('warning')); ?></span>
        <?php endif; ?>
        <?php if(session('info')): ?>
            <span data-admin-flash data-type="info"><?php echo e(session('info')); ?></span>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <span data-admin-flash data-type="error"><?php echo e($error); ?></span>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    </div>

    <div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

    
    <?php echo $__env->make('admin.layouts.partials.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <main>
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    
    <?php echo $__env->make('admin.layouts.partials.scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php echo app('Illuminate\Foundation\Vite')('resources/js/app.js'); ?>

</body>

</html>
<?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\layouts\admin.blade.php ENDPATH**/ ?>