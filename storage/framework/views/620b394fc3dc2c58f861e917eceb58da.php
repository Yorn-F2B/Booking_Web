<?php $__env->startSection('title', 'Thêm loại phòng'); ?>

<?php $__env->startSection('content'); ?>

<div class="admin-wrapper">

    <main class="admin-content">

        <p class="admin-breadcrumb mb-3">
            <a href="<?php echo e(route('room-categories.index')); ?>">Admin</a> / Thêm loại phòng
        </p>

        <div class="admin-page-head">
            <div>
                <h2>Thêm loại phòng</h2>
                <p>Thêm hạng phòng mới cho khách sạn</p>
            </div>
        </div>

        <form action="<?php echo e(route('room-categories.store')); ?>" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>

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
                        <input type="text" name="name" class="form-control" value="<?php echo e(old('name')); ?>">
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
                        <input type="number" name="price" class="form-control" value="<?php echo e(old('price')); ?>">
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
                            value="<?php echo e(old('adult_capacity')); ?>">
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
                            value="<?php echo e(old('child_capacity', 0)); ?>">
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
                            value="<?php echo e(old('bed_count', 1)); ?>">
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
                            value="<?php echo e(old('area')); ?>">
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
                            <option value="active" <?php echo e(old('status') == 'active' ? 'selected' : ''); ?>>
                                Đang hoạt động
                            </option>
                            <option value="inactive" <?php echo e(old('status') == 'inactive' ? 'selected' : ''); ?>>
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
                                    <?php echo e(in_array($amenity->id, old('amenities', [])) ? 'checked' : ''); ?>>

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
                    <label class="form-label">Ảnh đại diện</label>
                    <input type="file" name="thumbnail" id="roomCategoryThumbnail" class="form-control" accept="image/*" data-persistent-files>
                    <?php $__errorArgs = ['thumbnail'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Album ảnh</label>
                    <input type="file" name="images[]" id="roomCategoryImages" multiple class="form-control" accept="image/*" data-persistent-files>
                    <?php $__errorArgs = ['images'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="text-danger"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    <?php $__errorArgs = ['images.*'];
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

                <textarea name="description" rows="5" class="form-control"><?php echo e(old('description')); ?></textarea>
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
                    Lưu loại phòng
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
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/room-categories/create.blade.php ENDPATH**/ ?>