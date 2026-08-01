<?php $__env->startSection('title', 'Phân công nhân sự'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $canManageReceptionists = $canManageReceptionists ?? false;
        $canManageHousekeeping = $canManageHousekeeping ?? false;
        $today = $today ?? now('Asia/Ho_Chi_Minh')->toDateString();
    ?>

    <style>
        .assignment-page {
            --assignment-border: #e5e7eb;
            --assignment-soft: #f8fafc;
            --assignment-muted: #64748b;
            --assignment-ink: #111827;
            --assignment-gold: #d4af37;
        }

        .assignment-hero {
            border: 1px solid var(--assignment-border);
            border-radius: 22px;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 55%, #fff7db 100%);
            padding: 20px;
            margin-bottom: 18px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06);
        }

        .assignment-hero h2 {
            margin: 0;
            font-size: 26px;
            font-weight: 950;
            color: var(--assignment-ink);
            letter-spacing: -0.03em;
        }

        .assignment-hero p {
            margin: 6px 0 0;
            color: var(--assignment-muted);
            font-size: 14px;
            max-width: 760px;
        }

        .assignment-card {
            height: 100%;
            border: 1px solid var(--assignment-border);
            border-radius: 20px;
            background: #fff;
            padding: 18px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.045);
            transition: 0.16s ease;
        }

        .assignment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
        }

        .assignment-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .assignment-card-title {
            margin: 0;
            color: var(--assignment-ink);
            font-size: 18px;
            font-weight: 900;
        }

        .assignment-card-desc {
            margin: 5px 0 0;
            color: var(--assignment-muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .assignment-icon {
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            font-size: 26px;
        }

        .assignment-icon.primary {
            color: #2563eb;
            background: #eff6ff;
        }

        .assignment-icon.success {
            color: #16a34a;
            background: #f0fdf4;
        }

        .assignment-stat-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin: 14px 0 16px;
        }

        .assignment-stat-grid.single {
            grid-template-columns: 1fr;
        }

        .assignment-stat {
            border: 1px solid var(--assignment-border);
            border-radius: 16px;
            background: var(--assignment-soft);
            padding: 12px;
        }

        .assignment-stat span {
            display: block;
            color: var(--assignment-muted);
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .assignment-stat strong {
            display: block;
            color: var(--assignment-ink);
            font-size: 24px;
            line-height: 1;
            font-weight: 950;
        }

        .assignment-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .assignment-note {
            color: var(--assignment-muted);
            font-size: 12px;
        }

        .assignment-empty {
            border: 1px dashed #cbd5e1;
            border-radius: 18px;
            background: #f8fafc;
            padding: 20px;
            color: #64748b;
        }

        @media (max-width: 767px) {
            .assignment-hero {
                padding: 16px;
            }

            .assignment-stat-grid {
                grid-template-columns: 1fr;
            }

            .assignment-actions .btn {
                width: 100%;
            }
        }
    </style>

    <div class="admin-wrapper assignment-page">
        <main class="admin-content">
            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Phân công nhân sự
            </p>

            <section class="assignment-hero">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div>
                        <h2>Phân công nhân sự</h2>
                    </div>

                    <span class="badge bg-dark-subtle text-dark rounded-pill px-3 py-2">
                        Hôm nay: <?php echo e(\Carbon\Carbon::parse($today)->format('d/m/Y')); ?>

                    </span>
                </div>
            </section>

            <?php if(session('success')): ?>
                <div class="alert alert-success"><?php echo e(session('success')); ?></div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
            <?php endif; ?>

            <?php if(!$canManageReceptionists && !$canManageHousekeeping): ?>
                <div class="assignment-empty">
                    Bạn chưa có quyền phân công nhân sự. Vui lòng liên hệ quản lý hoặc super admin nếu cần cấp quyền.
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php if($canManageReceptionists): ?>
                        <div class="col-lg-6">
                            <section class="assignment-card">
                                <div class="assignment-card-head">
                                    <div>
                                        <h5 class="assignment-card-title">Lễ tân phụ trách booking</h5>
                                        <p class="assignment-card-desc">
                                            Gán booking cho lễ tân phụ trách chính, check-in, check-out, thanh toán hoặc hỗ trợ.
                                        </p>
                                    </div>
                                    <span class="assignment-icon primary">
                                        <i class="bx bx-calendar-check"></i>
                                    </span>
                                </div>

                                <div class="assignment-stat-grid single">
                                    <div class="assignment-stat">
                                        <span>Phân công đang hoạt động</span>
                                        <strong><?php echo e($receptionistAssignmentCount); ?></strong>
                                    </div>
                                </div>

                                <div class="assignment-actions">
                                    <a href="<?php echo e(route('admin.staff-assignments.receptionists')); ?>" class="btn btn-primary">
                                        <i class="bx bx-user-check me-1"></i> Gán lễ tân
                                    </a>
                                    <span class="assignment-note">Lễ tân thường chỉ thấy booking được giao hoặc do mình tạo.</span>
                                </div>
                            </section>
                        </div>
                    <?php endif; ?>

                    <?php if($canManageHousekeeping): ?>
                        <div class="col-lg-6">
                            <section class="assignment-card">
                                <div class="assignment-card-head">
                                    <div>
                                        <h5 class="assignment-card-title">Phân công buồng phòng</h5>
                                        <p class="assignment-card-desc">
                                            Gán nhanh theo tầng hoặc giao trực tiếp từng phòng trên cùng một màn hình, tránh tách nút trùng trang.
                                        </p>
                                    </div>
                                    <span class="assignment-icon success">
                                        <i class="bx bx-brush"></i>
                                    </span>
                                </div>

                                <div class="assignment-stat-grid">
                                    <div class="assignment-stat">
                                        <span>Tầng được gán hôm nay</span>
                                        <strong><?php echo e($floorAssignmentCount); ?></strong>
                                    </div>
                                    <div class="assignment-stat">
                                        <span>Phòng được giao hôm nay</span>
                                        <strong><?php echo e($roomAssignmentCount); ?></strong>
                                    </div>
                                </div>

                                <div class="assignment-actions">
                                    <a href="<?php echo e(route('admin.staff-assignments.housekeeping', ['work_date' => $today])); ?>" class="btn btn-success">
                                        <i class="bx bx-building-house me-1"></i> Gán buồng phòng
                                    </a>
                                    <span class="assignment-note">Một trang xử lý cả gán theo tầng và gán theo phòng.</span>
                                </div>
                            </section>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views/admin/pages/staff-assignments/index.blade.php ENDPATH**/ ?>