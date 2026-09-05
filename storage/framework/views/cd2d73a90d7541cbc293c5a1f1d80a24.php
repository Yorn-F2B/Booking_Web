<?php $__env->startSection('title', 'Tổng quan điều hành'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $money = fn ($value) => number_format((float) ($value ?? 0), 0, ',', '.') . 'đ';
    $compactMoney = function ($value) {
        $value = (float) ($value ?? 0);
        if ($value >= 1000000000) return rtrim(rtrim(number_format($value / 1000000000, 1, ',', '.'), '0'), ',') . ' tỷ';
        if ($value >= 1000000) return rtrim(rtrim(number_format($value / 1000000, 1, ',', '.'), '0'), ',') . ' triệu';
        if ($value >= 1000) return rtrim(rtrim(number_format($value / 1000, 1, ',', '.'), '0'), ',') . ' nghìn';
        return number_format($value, 0, ',', '.') . 'đ';
    };
    $statusLabels = [
        'pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'checked_in' => 'Đang ở',
        'inspection_requested' => 'Chờ kiểm tra', 'checked_out' => 'Đã trả phòng',
        'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy',
    ];
    $roomStatusLabels = [
        'available' => 'Trống', 'reserved' => 'Đã giữ', 'occupied' => 'Đang ở',
        'cleaning' => 'Đang dọn', 'inspection' => 'Chờ kiểm tra', 'maintenance' => 'Bảo trì',
    ];
    $trendPoints = collect(data_get($revenueTrend, 'points', []));
    $trendMax = max(1, (float) data_get($revenueTrend, 'max', 1));
    $sourceRows = collect($bookingSourceRows ?? []);
    $sourceMax = max(1, (int) $sourceRows->max('count'));
    $surchargeCollection = collect($surchargeRows ?? []);
    $surchargeMax = max(1, (float) $surchargeCollection->max('total'));
    $providerCollection = collect($paymentProviderRows ?? []);
    $providerMax = max(1, (float) $providerCollection->max('total'));
    $categoryRows = collect($categoryOccupancy ?? []);
    $statusTotal = max(1, (int) collect($bookingStatusRows ?? [])->sum());
    $changeIsPositive = $revenueChangePercent === null ? null : $revenueChangePercent >= 0;
?>

<style>
    .exec-dashboard{--navy:#0b1d38;--muted:#6b778c;--line:#e8edf3;--soft:#f4f7fa;--green:#28a36a;--lime:#d9ee5f;--blue:#3b82f6;--orange:#f59e0b;--red:#e25555;color:var(--navy)}
    .exec-top{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;margin-bottom:18px}
    .exec-top h1{font-size:32px;font-weight:850;margin:0 0 5px;letter-spacing:-.02em}.exec-top p{margin:0;color:var(--muted)}
    .exec-range{background:#fff;border:1px solid var(--line);border-radius:16px;padding:16px;margin-bottom:18px;box-shadow:0 6px 20px rgba(15,23,42,.04)}
    .exec-range-form{display:flex;gap:10px;align-items:end;flex-wrap:wrap}.exec-range-form .field{min-width:155px}.exec-range-form label{display:block;font-size:11px;font-weight:800;text-transform:uppercase;color:#7c8799;margin-bottom:5px;letter-spacing:.04em}
    .exec-presets{display:flex;gap:7px;flex-wrap:wrap;margin-top:11px}.exec-preset{display:inline-flex;padding:6px 10px;border-radius:999px;border:1px solid var(--line);color:#526078;font-size:12px;font-weight:700;text-decoration:none;background:#fff}.exec-preset.active,.exec-preset:hover{background:#eef5ff;border-color:#a8c8f5;color:#245a9b}
    .exec-kpis{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:18px}.exec-kpi{display:block;text-decoration:none;color:inherit;background:#fff;border:1px solid var(--line);border-radius:16px;padding:17px;min-height:130px;box-shadow:0 6px 20px rgba(15,23,42,.04)}
    .exec-kpi-icon{width:38px;height:38px;border-radius:11px;background:#eef5ff;display:flex;align-items:center;justify-content:center;font-size:20px;color:#2a65a8;margin-bottom:12px}.exec-kpi .label{font-size:12px;color:var(--muted);font-weight:700}.exec-kpi .value{font-size:24px;font-weight:900;margin-top:3px;white-space:nowrap}.exec-kpi .sub{font-size:11px;color:#8290a3;margin-top:7px;line-height:1.35}.exec-change{display:inline-flex;align-items:center;gap:4px;border-radius:999px;padding:3px 7px;font-size:11px;font-weight:800;margin-top:7px}.exec-change.up{background:#e9f8f1;color:#1f8b5b}.exec-change.down{background:#fff0f0;color:#c44949}.exec-change.neutral{background:#f2f4f7;color:#667085}
    .exec-grid{display:grid;grid-template-columns:1.5fr 1fr;gap:16px;margin-bottom:16px}.exec-grid>*{min-width:0}.exec-grid.equal{grid-template-columns:1fr 1fr}.exec-card{min-width:0;background:#fff;border:1px solid var(--line);border-radius:17px;padding:19px;box-shadow:0 6px 20px rgba(15,23,42,.04)}.exec-card h2{font-size:16px;font-weight:850;margin:0}.exec-card-head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;margin-bottom:16px}.exec-card-head p{font-size:12px;color:var(--muted);margin:4px 0 0}.exec-pill{font-size:11px;font-weight:800;padding:5px 8px;border-radius:999px;background:#f1f5f9;color:#5a667a;white-space:nowrap}
    .exec-trend{height:245px;display:flex;align-items:flex-end;gap:4px;border-bottom:1px solid var(--line);padding:15px 4px 0;overflow:hidden}.exec-trend-col{min-width:0;flex:1 1 0;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;height:100%;gap:5px;position:relative}.exec-trend-bar{width:min(74%,26px);min-width:3px;border-radius:7px 7px 2px 2px;background:linear-gradient(180deg,#93c5fd,#3b82f6);min-height:2px}.exec-trend-value{font-size:9px;color:#667085;white-space:nowrap;max-width:72px;overflow:hidden;text-overflow:ellipsis}.exec-trend-label{font-size:9px;color:#8691a3;white-space:nowrap;padding-bottom:6px;min-height:17px}.exec-trend-label.muted{visibility:hidden}
    .exec-progress-row{margin-bottom:13px}.exec-progress-meta{display:flex;justify-content:space-between;gap:10px;font-size:12px;margin-bottom:6px}.exec-progress-meta strong{font-weight:800}.exec-track{height:9px;border-radius:999px;background:#eef2f6;overflow:hidden}.exec-fill{height:100%;border-radius:999px;background:#9ed9c2}.exec-fill.lime{background:#d9ee5f}.exec-fill.blue{background:#72a9ef}.exec-fill.orange{background:#f4bd6a}.exec-fill.red{background:#ee9696}
    .exec-room-now{display:grid;grid-template-columns:repeat(4,1fr);gap:9px;margin-bottom:16px}.exec-room-stat{background:#f7f9fb;border-radius:12px;padding:11px;text-align:center}.exec-room-stat strong{font-size:20px;display:block}.exec-room-stat span{font-size:10px;color:#778397}
    .exec-source-row,.exec-money-row{display:grid;grid-template-columns:minmax(120px,1fr) 2fr auto;gap:10px;align-items:center;margin-bottom:12px;font-size:12px}.exec-source-row .exec-track,.exec-money-row .exec-track{height:8px}.exec-source-row strong,.exec-money-row strong{font-size:12px;white-space:nowrap}
    .exec-status-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:9px}.exec-status{background:#f7f9fb;border-radius:12px;padding:11px}.exec-status .n{font-weight:900;font-size:20px}.exec-status .t{font-size:11px;color:#778397}
    .exec-alerts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.exec-alert{border-radius:13px;padding:13px;border:1px solid var(--line);background:#fafbfc;display:flex;flex-direction:column;min-height:145px}.exec-alert.warning{background:#fff9e9;border-color:#f4df9d}.exec-alert.danger{background:#fff2f2;border-color:#f2baba}.exec-alert.info{background:#f1f7ff;border-color:#bfd7f5}.exec-alert.success{background:#eefaf4;border-color:#b9e6ce}.exec-alert .title{font-size:12px;font-weight:850}.exec-alert .value{font-size:18px;font-weight:900;margin-top:4px}.exec-alert .detail{font-size:10px;color:#68758a;margin-top:4px;line-height:1.45}.exec-alert-action{margin-top:auto;padding-top:10px;font-size:11px;font-weight:800;color:#245a9b;text-decoration:none;display:inline-flex;align-items:center;gap:4px}.exec-alert-action:hover{text-decoration:underline}
    .exec-empty{padding:28px;text-align:center;color:#8792a4;font-size:12px;border:1px dashed #dce3eb;border-radius:12px;background:#fafbfd}
    @media(max-width:1350px){.exec-kpis{grid-template-columns:repeat(3,1fr)}}@media(max-width:900px){.exec-grid,.exec-grid.equal{grid-template-columns:1fr}.exec-alerts{grid-template-columns:1fr}.exec-kpis{grid-template-columns:repeat(2,1fr)}}@media(max-width:560px){.exec-kpis{grid-template-columns:1fr}.exec-room-now{grid-template-columns:repeat(2,1fr)}.exec-top{flex-direction:column}}
</style>

<div class="admin-wrapper">
    <div class="admin-content">
<div class="exec-dashboard">
    <div class="exec-top">
        <div>
            <h1>Tổng quan điều hành</h1>
            <p>Super Admin · Theo dõi sức khỏe kinh doanh và vận hành khách sạn.</p>
        </div>
        <div class="text-end small text-muted">
            <div class="fw-bold text-dark">MCuong Hotel</div>
            Cập nhật <?php echo e($now->format('H:i d/m/Y')); ?>

        </div>
    </div>

    <section class="exec-range">
        <form method="GET" action="<?php echo e(route('admin.dashboard')); ?>" class="exec-range-form">
            <div class="field"><label>Từ ngày</label><input type="date" name="from" class="form-control" value="<?php echo e($from->format('Y-m-d')); ?>"></div>
            <div class="field"><label>Đến ngày</label><input type="date" name="to" class="form-control" value="<?php echo e($to->format('Y-m-d')); ?>"></div>
            <button class="btn btn-primary" type="submit"><i class="bx bx-filter-alt me-1"></i>Xem báo cáo</button>
            <div class="ms-auto small text-muted"><strong class="text-dark">Kỳ đang xem:</strong> <?php echo e($periodLabel); ?> · <?php echo e($periodDays); ?> ngày</div>
        </form>
        <div class="exec-presets">
            <?php $__currentLoopData = ['today'=>'Hôm nay','yesterday'=>'Hôm qua','7d'=>'7 ngày','30d'=>'30 ngày','this_month'=>'Tháng này','last_month'=>'Tháng trước','this_year'=>'Năm nay']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a class="exec-preset <?php echo e($preset === $key ? 'active' : ''); ?>" href="<?php echo e(route('admin.dashboard', ['preset'=>$key])); ?>"><?php echo e($label); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php if($preset === 'custom'): ?><span class="exec-preset active">Khoảng tùy chọn</span><?php endif; ?>
        </div>
    </section>

    <section class="exec-kpis">
        <a class="exec-kpi" href="<?php echo e(route('admin.dashboard.detail',['metric'=>'revenue','from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')])); ?>">
            <div class="exec-kpi-icon"><i class="bx bx-wallet"></i></div><div class="label">Doanh thu thuần thực thu</div><div class="value"><?php echo e($compactMoney($revenue)); ?></div>
            <?php if($revenueChangePercent === null): ?><div class="exec-change neutral">Kỳ trước 0đ</div><?php elseif($revenueChangePercent > 0): ?><div class="exec-change up"><i class="bx bx-trending-up"></i> +<?php echo e($revenueChangePercent); ?>%</div><?php elseif($revenueChangePercent < 0): ?><div class="exec-change down"><i class="bx bx-trending-down"></i> <?php echo e($revenueChangePercent); ?>%</div><?php else: ?><div class="exec-change neutral">Không đổi</div><?php endif; ?>
            <div class="sub">Thu vào <?php echo e($compactMoney($grossRevenue)); ?> − đã hoàn <?php echo e($compactMoney($refundAmount)); ?> · kỳ trước: <?php echo e($compactMoney($previousRevenue)); ?></div>
        </a>
        <a class="exec-kpi" href="<?php echo e(route('admin.dashboard.detail',['metric'=>'booking_value','from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')])); ?>"><div class="exec-kpi-icon"><i class="bx bx-receipt"></i></div><div class="label">Giá trị booking phát sinh</div><div class="value"><?php echo e($compactMoney($bookingValue)); ?></div><div class="sub">Không tính booking đã hủy · dựa trên đơn tạo trong kỳ · bấm để xem chi tiết</div></a>
        <article class="exec-kpi"><div class="exec-kpi-icon"><i class="bx bx-bed"></i></div><div class="label">Công suất phòng bình quân</div><div class="value"><?php echo e(number_format((float)data_get($occupancy,'rate',0),1,',','.')); ?>%</div><div class="sub"><?php echo e(data_get($occupancy,'occupied_room_days',0)); ?> / <?php echo e(data_get($occupancy,'capacity_room_days',0)); ?> phòng-ngày</div></article>
        <a class="exec-kpi" href="<?php echo e(route('admin.dashboard.detail',['metric'=>'new_bookings','from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')])); ?>"><div class="exec-kpi-icon"><i class="bx bx-calendar-check"></i></div><div class="label">Booking mới</div><div class="value"><?php echo e($newBookings); ?></div><div class="sub">Hoàn tất/trả phòng: <?php echo e($completedBookings); ?> · Hủy: <?php echo e($cancelledBookings); ?> · No-show: <?php echo e($noShowCount); ?> · bấm để xem</div></a>
        <a class="exec-kpi" href="<?php echo e(route('admin.dashboard.detail',['metric'=>'receivables','from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')])); ?>"><div class="exec-kpi-icon"><i class="bx bx-credit-card"></i></div><div class="label">Công nợ liên quan kỳ</div><div class="value"><?php echo e($compactMoney($receivableAmount)); ?></div><div class="sub"><?php echo e($receivableBookings); ?> booking còn số tiền phải thu · bấm để xem</div></a>
        <a class="exec-kpi" href="<?php echo e(route('admin.dashboard.detail',['metric'=>'discounts','from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')])); ?>"><div class="exec-kpi-icon"><i class="bx bx-purchase-tag-alt"></i></div><div class="label">Ưu đãi / giảm giá</div><div class="value"><?php echo e($compactMoney($discountAmount)); ?></div><div class="sub">Tổng discount snapshot của booking tạo trong kỳ · bấm để xem</div></a>
    </section>

    <section class="exec-kpis">
        <article class="exec-kpi"><div class="exec-kpi-icon"><i class="bx bx-user-check"></i></div><div class="label">Khách đang lưu trú</div><div class="value"><?php echo e($activeGuests); ?></div><div class="sub"><?php echo e($activeStays); ?> booking đang checked-in</div></article>
        <article class="exec-kpi"><div class="exec-kpi-icon"><i class="bx bx-line-chart"></i></div><div class="label">Giá trị booking trung bình</div><div class="value"><?php echo e($compactMoney($averageBookingValue)); ?></div><div class="sub">Giá trị booking chưa hủy / <?php echo e($nonCancelledBookings); ?> booking hợp lệ trong kỳ</div></article>
        <a class="exec-kpi" href="<?php echo e(route('admin.dashboard.detail',['metric'=>'pending_payments','from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')])); ?>"><div class="exec-kpi-icon"><i class="bx bx-time-five"></i></div><div class="label">Thanh toán chờ xử lý</div><div class="value"><?php echo e($pendingPayments); ?></div><div class="sub">Payment pending tạo trong kỳ · bấm để xem</div></a>
        <a class="exec-kpi" href="<?php echo e(route('admin.dashboard.detail',['metric'=>'failed_payments','from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')])); ?>"><div class="exec-kpi-icon"><i class="bx bx-error-circle"></i></div><div class="label">Thanh toán thất bại</div><div class="value"><?php echo e($failedPayments); ?></div><div class="sub">Payment failed trong kỳ · bấm để xem</div></a>
        <article class="exec-kpi"><div class="exec-kpi-icon"><i class="bx bx-wrench"></i></div><div class="label">Sự cố phòng đang mở</div><div class="value"><?php echo e($openRoomIssues); ?></div><div class="sub">Cần theo dõi/giải quyết trong vận hành hiện tại</div></article>
        <a class="exec-kpi" href="<?php echo e(route('admin.dashboard.detail',['metric'=>'failed_emails','from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')])); ?>"><div class="exec-kpi-icon"><i class="bx bx-envelope"></i></div><div class="label">Email lỗi trong kỳ</div><div class="value"><?php echo e($failedEmails); ?></div><div class="sub">Gửi thất bại được hệ thống ghi log · bấm để xem</div></a>
        <a class="exec-kpi" href="<?php echo e(route('admin.dashboard.detail',['metric'=>'refunds','from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')])); ?>"><div class="exec-kpi-icon"><i class="bx bx-undo"></i></div><div class="label">Tiền đã hoàn khách</div><div class="value"><?php echo e($compactMoney($refundAmount)); ?></div><div class="sub">Các khoản hoàn đã xác nhận hoàn tất trong kỳ · bấm để xem</div></a>
    </section>

    <section class="exec-grid">
        <article class="exec-card">
            <div class="exec-card-head"><div><h2>Xu hướng tiền thu vào</h2><p>Giao dịch payment thành công theo thời điểm thu; khoản hoàn được theo dõi riêng ở KPI phía trên.</p></div><span class="exec-pill"><?php echo e($periodLabel); ?></span></div>
            <?php if($trendPoints->isEmpty()): ?><div class="exec-empty">Chưa có giao dịch thành công trong khoảng này.</div><?php else: ?>
                <div class="exec-trend">
                    <?php $__currentLoopData = $trendPoints; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $point): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $height=max(2,min(100,((float)$point['value']/$trendMax)*100)); ?>
                        <div class="exec-trend-col" title="<?php echo e($point['label']); ?>: <?php echo e($money($point['value'])); ?>">
                            <div class="exec-trend-value"><?php echo e(!empty($point['show_value']) ? $compactMoney($point['value']) : ''); ?></div>
                            <div class="exec-trend-bar" style="height:<?php echo e($height); ?>%"></div>
                            <div class="exec-trend-label <?php echo e(empty($point['show_label']) ? 'muted' : ''); ?>"><?php echo e($point['label']); ?></div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
        </article>
        <article class="exec-card">
            <div class="exec-card-head"><div><h2>Công suất & trạng thái phòng</h2><p>Công suất theo kỳ; trạng thái phòng là tình hình hiện tại.</p></div><span class="exec-pill"><?php echo e($totalRooms); ?> phòng</span></div>
            <div class="exec-room-now"><div class="exec-room-stat"><strong><?php echo e($currentAvailableRooms); ?></strong><span>Trống</span></div><div class="exec-room-stat"><strong><?php echo e($currentOccupiedRooms); ?></strong><span>Đang ở</span></div><div class="exec-room-stat"><strong><?php echo e((int)($roomStatuses['reserved'] ?? 0)); ?></strong><span>Đã giữ</span></div><div class="exec-room-stat"><strong><?php echo e($currentNotReadyRooms); ?></strong><span>Chưa sẵn sàng</span></div></div>
            <?php $__empty_1 = true; $__currentLoopData = $categoryRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><div class="exec-progress-row"><div class="exec-progress-meta"><strong><?php echo e($row['name']); ?></strong><span><?php echo e($row['rate']); ?>% · <?php echo e($row['room_count']); ?> phòng</span></div><div class="exec-track"><div class="exec-fill" style="width:<?php echo e(max(0,min(100,$row['rate']))); ?>%"></div></div></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div class="exec-empty">Chưa có dữ liệu hạng phòng.</div><?php endif; ?>
        </article>
    </section>

    <section class="exec-grid equal">
        <article class="exec-card">
            <div class="exec-card-head"><div><h2>Booking theo kênh tiếp nhận</h2><p>Đơn được tạo trong khoảng đã chọn.</p></div><span class="exec-pill"><?php echo e($newBookings); ?> đơn</span></div>
            <?php $__empty_1 = true; $__currentLoopData = $sourceRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><div class="exec-source-row"><span><?php echo e($row['label']); ?></span><div class="exec-track"><div class="exec-fill lime" style="width:<?php echo e((($row['count']/$sourceMax)*100)); ?>%"></div></div><strong><?php echo e($row['count']); ?></strong></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div class="exec-empty">Chưa có booking trong kỳ.</div><?php endif; ?>
        </article>
        <article class="exec-card">
            <div class="exec-card-head"><div><h2>Phụ thu & phí phát sinh</h2><p>Tổng hợp các khoản đã xác nhận trong khoảng đã chọn.</p></div><span class="exec-pill"><?php echo e($compactMoney($surchargeCollection->sum('total'))); ?></span></div>
            <?php $__empty_1 = true; $__currentLoopData = $surchargeCollection; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><div class="exec-money-row"><span><?php echo e($row['label']); ?></span><div class="exec-track"><div class="exec-fill orange" style="width:<?php echo e((($row['total']/$surchargeMax)*100)); ?>%"></div></div><strong><?php echo e($compactMoney($row['total'])); ?></strong></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div class="exec-empty">Không phát sinh phụ thu trong kỳ.</div><?php endif; ?>
        </article>
    </section>

    <section class="exec-grid equal">
        <article class="exec-card">
            <div class="exec-card-head"><div><h2>Cơ cấu tiền thu vào</h2><p>Phân theo phương thức/provider của giao dịch thành công, trước khi trừ hoàn tiền.</p></div><span class="exec-pill"><?php echo e($compactMoney($grossRevenue)); ?></span></div>
            <?php $__empty_1 = true; $__currentLoopData = $providerCollection; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><div class="exec-money-row"><span><?php echo e($row['label']); ?></span><div class="exec-track"><div class="exec-fill blue" style="width:<?php echo e((($row['total']/$providerMax)*100)); ?>%"></div></div><strong><?php echo e($compactMoney($row['total'])); ?></strong></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div class="exec-empty">Chưa có khoản thu trong kỳ.</div><?php endif; ?>
        </article>
        <article class="exec-card">
            <div class="exec-card-head"><div><h2>Trạng thái booking</h2><p>Cơ cấu các booking được tạo trong kỳ.</p></div><span class="exec-pill">Hủy <?php echo e($cancelledBookings); ?> · No-show <?php echo e($noShowCount); ?></span></div>
            <div class="exec-status-grid">
                <?php $__currentLoopData = $statusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $count=(int)($bookingStatusRows[$status] ?? 0); ?>
                    <div class="exec-status"><div class="n"><?php echo e($count); ?></div><div class="t"><?php echo e($label); ?> · <?php echo e(number_format(($count/$statusTotal)*100,1,',','.')); ?>%</div></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </article>
    </section>

</div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/dashboard/dashboard.blade.php ENDPATH**/ ?>