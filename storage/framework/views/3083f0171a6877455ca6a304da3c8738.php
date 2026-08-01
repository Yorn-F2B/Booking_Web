<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo $__env->yieldContent('title'); ?></title>

    <link rel="icon" type="image/svg+xml" href="<?php echo e(asset('favicon.svg')); ?>">
    <link rel="shortcut icon" href="<?php echo e(asset('favicon.svg')); ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://npmcdn.com/flatpickr/dist/themes/material_blue.css">
    <link rel="stylesheet" href="<?php echo e(asset('assets/css/project-date-picker.css')); ?>?v=<?php echo e(filemtime(public_path('assets/css/project-date-picker.css'))); ?>">

    <link rel="stylesheet" href="<?php echo e(asset('assets/css/style.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/css/admin.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/css/admin-unified.css')); ?>?v=<?php echo e(filemtime(public_path('assets/css/admin-unified.css'))); ?>">

    <style>
        .admin-toast-stack {
            position: fixed;
            top: 18px;
            right: 20px;
            z-index: 100500;
            width: min(390px, calc(100vw - 32px));
            display: grid;
            gap: 10px;
            pointer-events: none;
        }

        .admin-toast {
            --toast-color: #2563eb;
            --toast-background: #eff6ff;
            --toast-border: #bfdbfe;
            position: relative;
            display: grid;
            grid-template-columns: 24px 1fr 28px;
            gap: 10px;
            align-items: start;
            overflow: hidden;
            padding: 14px 12px 16px 14px;
            border: 1px solid var(--toast-border);
            border-left: 4px solid var(--toast-color);
            border-radius: 12px;
            background: var(--toast-background);
            color: #172033;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .18);
            opacity: 0;
            transform: translateX(28px);
            transition: opacity .22s ease, transform .22s ease;
            pointer-events: auto;
        }

        .admin-toast.is-visible {
            opacity: 1;
            transform: translateX(0);
        }

        .admin-toast-success {
            --toast-color: #16866f;
            --toast-background: #ecfdf5;
            --toast-border: #a7f3d0;
        }

        .admin-toast-error {
            --toast-color: #dc3545;
            --toast-background: #fff1f2;
            --toast-border: #fecdd3;
        }

        .admin-toast-warning {
            --toast-color: #a66f00;
            --toast-background: #fffbeb;
            --toast-border: #fde68a;
        }

        .admin-toast-info {
            --toast-color: #2563eb;
            --toast-background: #eff6ff;
            --toast-border: #bfdbfe;
        }

        .admin-toast > i {
            color: var(--toast-color);
            font-size: 22px;
        }

        .admin-toast-message {
            font-size: 13px;
            font-weight: 600;
            line-height: 1.5;
            white-space: pre-line;
        }

        .admin-toast-close {
            width: 28px;
            height: 28px;
            padding: 0;
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: #64748b;
            font-size: 22px;
            line-height: 1;
        }

        .admin-toast-close:hover {
            background: #f1f5f9;
            color: #172033;
        }

        .admin-toast-progress {
            position: absolute;
            right: 0;
            bottom: 0;
            left: 0;
            height: 3px;
            background: var(--toast-color);
            transform-origin: left;
            animation: admin-toast-countdown 15s linear forwards;
        }

        @keyframes admin-toast-countdown {
            to { transform: scaleX(0); }
        }

        @media (max-width: 767px) {
            .admin-toast-stack {
                top: 12px;
                right: 12px;
            }
        }
    </style>
</head>
<?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views/admin/layouts/partials/head.blade.php ENDPATH**/ ?>