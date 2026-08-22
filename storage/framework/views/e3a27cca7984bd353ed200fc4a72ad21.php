<?php
    $scoreOptions = [5 => '5 sao - Rất tốt', 4 => '4 sao - Tốt', 3 => '3 sao - Bình thường', 2 => '2 sao - Chưa hài lòng', 1 => '1 sao - Kém'];
?>

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Đánh giá tổng thể <span class="text-danger">*</span></label>
        <select name="rating" class="form-select" required>
            <option value="">Chọn số sao</option>
            <?php $__currentLoopData = $scoreOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $score => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($score); ?>" <?php echo e((string) old('rating', $review->rating ?? '') === (string) $score ? 'selected' : ''); ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Sạch sẽ <span class="text-danger">*</span></label>
        <select name="cleanliness_rating" class="form-select" required>
            <option value="">Chọn điểm</option>
            <?php $__currentLoopData = $scoreOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $score => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($score); ?>" <?php echo e((string) old('cleanliness_rating', $review->cleanliness_rating ?? '') === (string) $score ? 'selected' : ''); ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Chất lượng / tiện nghi phòng <span class="text-danger">*</span></label>
        <select name="room_quality_rating" class="form-select" required>
            <option value="">Chọn điểm</option>
            <?php $__currentLoopData = $scoreOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $score => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($score); ?>" <?php echo e((string) old('room_quality_rating', $review->room_quality_rating ?? '') === (string) $score ? 'selected' : ''); ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Nhân viên <span class="text-danger">*</span></label>
        <select name="staff_rating" class="form-select" required>
            <option value="">Chọn điểm</option>
            <?php $__currentLoopData = $scoreOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $score => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($score); ?>" <?php echo e((string) old('staff_rating', $review->staff_rating ?? '') === (string) $score ? 'selected' : ''); ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Dịch vụ <span class="text-danger">*</span></label>
        <select name="service_rating" class="form-select" required>
            <option value="">Chọn điểm</option>
            <?php $__currentLoopData = $scoreOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $score => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($score); ?>" <?php echo e((string) old('service_rating', $review->service_rating ?? '') === (string) $score ? 'selected' : ''); ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Thoải mái <span class="text-danger">*</span></label>
        <select name="comfort_rating" class="form-select" required>
            <option value="">Chọn điểm</option>
            <?php $__currentLoopData = $scoreOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $score => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($score); ?>" <?php echo e((string) old('comfort_rating', $review->comfort_rating ?? '') === (string) $score ? 'selected' : ''); ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Giá trị so với chi phí <span class="text-danger">*</span></label>
        <select name="value_rating" class="form-select" required>
            <option value="">Chọn điểm</option>
            <?php $__currentLoopData = $scoreOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $score => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($score); ?>" <?php echo e((string) old('value_rating', $review->value_rating ?? '') === (string) $score ? 'selected' : ''); ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Tiêu đề ngắn</label>
        <input type="text" name="title" class="form-control" maxlength="150"
            value="<?php echo e(old('title', $review->title ?? '')); ?>"
            placeholder="VD: Phòng sạch, nhân viên hỗ trợ tốt">
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Nội dung đánh giá <span class="text-danger">*</span></label>
        <textarea name="comment" rows="6" class="form-control" required maxlength="1500"
            placeholder="Chia sẻ trải nghiệm thực tế của bạn về phòng, tiện nghi, nhân viên, dịch vụ, sự thoải mái..."><?php echo e(old('comment', $review->comment ?? '')); ?></textarea>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\booking-web\resources\views/user/reviews/_form.blade.php ENDPATH**/ ?>