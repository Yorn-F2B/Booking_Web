<?php $__env->startSection('title', 'Sửa loại phòng'); ?>

<?php $__env->startSection('content'); ?>

<div class="admin-wrapper">

    <main class="admin-content">

        <p class="admin-breadcrumb mb-3">
            <a href="<?php echo e(route('room-categories.index')); ?>">Admin</a> / Sửa loại phòng
        </p>

        <div class="admin-page-head">
            <div>
                <h2>Sửa loại phòng</h2>
                <p>Cập nhật thông tin hạng phòng khách sạn</p>
            </div>
        </div>

        <form action="<?php echo e(route('room-categories.update', $roomCategory->id)); ?>" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger">
                    <strong>Có lỗi xảy ra:</strong>
                    <ul class="mb-0">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="settings-section mb-4">
                <h5 class="mb-3">Thông tin cơ bản</h5>

                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="form-label">Tên loại phòng</label>
                        <input type="text" name="name" class="form-control"
                            value="<?php echo e(old('name', $roomCategory->name)); ?>">
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="form-label">Giá phòng</label>
                        <input type="number" name="price" class="form-control"
                            value="<?php echo e(old('price', $roomCategory->price)); ?>">
                        <?php $__errorArgs = ['price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="col-lg-4 mb-3">
                        <label class="form-label">Người lớn tối đa</label>
                        <input type="number" name="adult_capacity" class="form-control"
                            value="<?php echo e(old('adult_capacity', $roomCategory->adult_capacity)); ?>">
                        <?php $__errorArgs = ['adult_capacity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="col-lg-4 mb-3">
                        <label class="form-label">Trẻ em tối đa</label>
                        <input type="number" name="child_capacity" class="form-control"
                            value="<?php echo e(old('child_capacity', $roomCategory->child_capacity)); ?>">
                        <?php $__errorArgs = ['child_capacity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="col-lg-4 mb-3">
                        <label class="form-label">Số giường</label>
                        <input type="number" name="bed_count" class="form-control"
                            value="<?php echo e(old('bed_count', $roomCategory->bed_count)); ?>">
                        <?php $__errorArgs = ['bed_count'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="form-label">Diện tích</label>
                        <input type="number" step="0.01" name="area" class="form-control"
                            value="<?php echo e(old('area', $roomCategory->area)); ?>">
                        <?php $__errorArgs = ['area'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo e(old('status', $roomCategory->status) == 'active' ? 'selected' : ''); ?>>
                                Đang hoạt động
                            </option>
                            <option value="inactive" <?php echo e(old('status', $roomCategory->status) == 'inactive' ? 'selected' : ''); ?>>
                                Tạm ẩn
                            </option>
                        </select>
                        <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <div class="settings-section mb-4">
                <h5 class="mb-3">Tiện ích</h5>

                <div class="row">
                    <?php $__empty_1 = true; $__currentLoopData = $amenities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $amenity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="col-md-4 col-lg-3 mb-3">
                            <label class="d-flex align-items-center gap-2 p-3 border rounded h-100">
                                <input type="checkbox"
                                    name="amenities[]"
                                    value="<?php echo e($amenity->id); ?>"
                                    <?php echo e(in_array($amenity->id, old('amenities', $roomCategory->amenities->pluck('id')->toArray())) ? 'checked' : ''); ?>>

                                <?php if($amenity->icon): ?>
                                    <i class="<?php echo e($amenity->icon); ?>" style="font-size: 20px;"></i>
                                <?php endif; ?>

                                <span><?php echo e($amenity->name); ?></span>
                            </label>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="col-12">
                            <span class="text-muted">Chưa có tiện ích nào</span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php $__errorArgs = ['amenities'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="settings-section mb-4">
                <h5 class="mb-3">Hình ảnh</h5>

                <div class="mb-3">
                    <label class="form-label">Ảnh thumbnail</label>
                    <input type="file" name="thumbnail" id="roomCategoryThumbnail" class="form-control" accept="image/*" data-persistent-files>

                    <?php if($roomCategory->thumbnail): ?>
                        <div class="mt-3">
                            <p class="text-muted mb-2">Ảnh hiện tại:</p>
                            <img src="<?php echo e(asset('storage/' . $roomCategory->thumbnail)); ?>" width="180" height="120"
                                style="object-fit: cover; border-radius: 10px;">
                        </div>
                    <?php endif; ?>
                </div>

                <?php if($roomCategory->images->count()): ?>
                    <div class="mb-3">
                        <label class="form-label">Album hiện tại</label>
                        <div class="row g-3">
                            <?php $__currentLoopData = $roomCategory->images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="col-6 col-md-4 col-xl-3 js-existing-room-image">
                                    <div class="d-block border rounded-3 p-2 h-100 bg-white position-relative">
                                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 js-mark-room-image-delete" style="z-index:2" title="Xóa">×</button>
                                        <a href="<?php echo e(asset('storage/' . $image->image)); ?>" target="_blank">
                                            <img src="<?php echo e(asset('storage/' . $image->image)); ?>" class="w-100 rounded-2"
                                                style="height:140px;object-fit:cover"
                                                onerror="this.closest('.js-existing-room-image').classList.add('border','border-danger')">
                                        </a>
                                        <input class="d-none js-delete-room-image-input" type="checkbox" name="delete_image_ids[]" value="<?php echo e($image->id); ?>">
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Thêm ảnh album mới</label>
                    <input type="file" name="images[]" id="roomCategoryImages" multiple class="form-control" accept="image/*" data-persistent-files>
                    <?php $__errorArgs = ['delete_image_ids.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="settings-section mb-4">
                <h5 class="mb-3">Mô tả</h5>

                <textarea name="description" rows="5"
                    class="form-control"><?php echo e(old('description', $roomCategory->description)); ?></textarea>

                <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-gold">
                    Cập nhật loại phòng
                </button>

                <a href="<?php echo e(route('room-categories.index')); ?>" class="btn btn-outline-secondary">
                    Quay lại
                </a>
            </div>

        </form>

    </main>

    <footer class="admin-footer">
        <span>MCuong Hotel Admin</span>
    </footer>

</div>


<script src="<?php echo e(asset('assets/js/persistent-file-inputs.js')); ?>?v=<?php echo e(filemtime(public_path('assets/js/persistent-file-inputs.js'))); ?>"></script>

<script>
document.addEventListener('click', function (event) {
    const button = event.target.closest('.js-mark-room-image-delete');
    if (!button) return;
    const item = button.closest('.js-existing-room-image');
    const checkbox = item?.querySelector('.js-delete-room-image-input');
    if (!item || !checkbox) return;
    checkbox.checked = true;
    item.classList.add('d-none');
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\room-categories\edit.blade.php ENDPATH**/ ?>