<?php
    $flashMessages = [];

    foreach (['success', 'error', 'warning', 'info'] as $flashType) {
        $value = session($flashType);
        if (is_string($value) && trim($value) !== '') {
            $flashMessages[] = ['type' => $flashType, 'message' => trim($value)];
        }
    }

    $status = session('status');
    $statusMessages = [
        'profile-updated' => 'Cập nhật thông tin cá nhân thành công.',
        'password-updated' => 'Đổi mật khẩu thành công.',
        'verification-link-sent' => 'Đã gửi lại liên kết xác minh email.',
    ];
    if (is_string($status) && trim($status) !== '') {
        $flashMessages[] = [
            'type' => 'info',
            'message' => $statusMessages[$status] ?? $status,
        ];
    }

    if (isset($errors) && $errors->any()) {
        $validationErrors = collect($errors->all())
            ->filter(fn ($error) => is_string($error) && trim($error) !== '')
            ->map(fn ($error) => trim($error))
            ->unique()
            ->values();

        if ($validationErrors->isNotEmpty()) {
            $message = $validationErrors->first();
            if ($validationErrors->count() > 1) {
                $message .= ' (Còn ' . ($validationErrors->count() - 1) . ' lỗi khác; xem ngay tại các trường được đánh dấu.)';
            }
            $flashMessages[] = ['type' => 'error', 'message' => $message];
        }
    }

    $seen = [];
    $flashMessages = array_values(array_filter($flashMessages, function ($item) use (&$seen) {
        $key = $item['type'] . '|' . preg_replace('/\s+/u', ' ', trim($item['message']));
        if (isset($seen[$key])) {
            return false;
        }
        $seen[$key] = true;
        return true;
    }));
?>

<?php if(count($flashMessages)): ?>
    <div data-app-flash-source hidden aria-hidden="true">
        <?php $__currentLoopData = $flashMessages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $flash): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <span data-app-flash data-type="<?php echo e($flash['type']); ?>"><?php echo e($flash['message']); ?></span>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>

<link rel="stylesheet" href="<?php echo e(asset('assets/css/flash-toast.css')); ?>?v=<?php echo e(filemtime(public_path('assets/css/flash-toast.css'))); ?>">
<script src="<?php echo e(asset('assets/js/flash-toast.js')); ?>?v=<?php echo e(filemtime(public_path('assets/js/flash-toast.js'))); ?>" defer></script>
<?php /**PATH C:\xampp\htdocs\booking-web\resources\views/partials/flash-toasts.blade.php ENDPATH**/ ?>