<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <?php echo $__env->make('admin.layouts.partials.head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

</head>

<body class="admin-page"
    <?php if(auth()->guard()->check()): ?>
        data-auth-user-id="<?php echo e(auth()->id()); ?>"
        data-auth-user-role="<?php echo e(auth()->user()->role); ?>"
    <?php endif; ?>>
    <?php echo $__env->make('partials.flash-toasts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.global-validation-errors', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

    
    <?php echo $__env->make('admin.layouts.partials.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <main>
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    
    <?php echo $__env->make('admin.layouts.partials.scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php echo $__env->make('partials.camera-capture', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php echo app('Illuminate\Foundation\Vite')('resources/js/app.js'); ?>

    <?php if(auth()->guard()->check()): ?>
        <?php if(in_array(auth()->user()->role, ['receptionist', 'receptionist_lead'], true)): ?>
            <script>
                (function () {
                    const url = <?php echo json_encode(route('admin.chats.presence.heartbeat'), 15, 512) ?>;
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

                    const heartbeat = async function () {
                        try {
                            await fetch(url, {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': csrf,
                                },
                            });
                        } catch (error) {
                            console.debug('Chat heartbeat unavailable.', error);
                        }
                    };

                    heartbeat();
                    window.setInterval(heartbeat, 45000);
                })();
            </script>
        <?php endif; ?>
    <?php endif; ?>

    <script src="<?php echo e(asset('assets/js/form-validation-hints.js')); ?>?v=<?php echo e(filemtime(public_path('assets/js/form-validation-hints.js'))); ?>"></script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>

</html>
<?php /**PATH C:\xampp\htdocs\booking-web\resources\views/layouts/admin.blade.php ENDPATH**/ ?>