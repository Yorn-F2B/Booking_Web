<?php $__env->startSection('title','Trung tâm công việc'); ?>
<?php $__env->startSection('content'); ?>
<div class="admin-wrapper"><div class="admin-content">
<div class="container-fluid py-3">
 <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
  <div><h1 class="h3 fw-bold mb-1">Trung tâm công việc</h1><div class="text-muted">Các việc đang còn mở được hệ thống tổng hợp theo vai trò; không cần mở từng booking để dò.</div></div>
  <form method="POST" action="<?php echo e(route('admin.notifications.read-all')); ?>"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?><button class="btn btn-outline-secondary btn-sm">Đánh dấu thông báo đã đọc</button></form>
 </div>
 <div class="row g-3">
  <div class="col-xl-7"><div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h5 fw-bold">Việc cần xử lý <span class="badge bg-danger-subtle text-danger"><?php echo e($tasks->count()); ?></span></h2>
   <?php $__empty_1 = true; $__currentLoopData = $tasks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $task): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><a href="<?php echo e($task['url']); ?>" class="d-flex text-decoration-none text-dark border rounded-3 p-3 mt-2 gap-3 align-items-start">
    <span class="badge <?php echo e($task['priority']==='high'?'bg-danger':($task['priority']==='medium'?'bg-warning text-dark':'bg-secondary')); ?>"><?php echo e($task['priority']==='high'?'Gấp':($task['priority']==='medium'?'Cần làm':'Theo dõi')); ?></span>
    <div class="flex-grow-1"><div class="fw-bold"><?php echo e($task['title']); ?></div><div class="small text-muted"><?php echo e($task['detail']); ?></div></div><i class="bx bx-chevron-right fs-4"></i>
   </a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div class="alert alert-success mb-0">Hiện không có công việc tồn cần xử lý.</div><?php endif; ?>
  </div></div></div>
  <div class="col-xl-5"><div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h5 fw-bold">Thông báo</h2>
   <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><a href="<?php echo e(route('admin.notifications.open',$n)); ?>" class="d-block text-decoration-none text-dark border-bottom py-3 <?php echo e($n->read_at?'opacity-75':''); ?>"><div class="d-flex justify-content-between gap-2"><strong><?php echo e($n->title); ?></strong><?php if(!$n->read_at): ?><span class="badge bg-danger rounded-pill">Mới</span><?php endif; ?></div><div class="small text-muted mt-1"><?php echo e($n->message); ?></div><div class="small text-muted mt-1"><?php echo e($n->created_at?->format('H:i d/m/Y')); ?></div></a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div class="text-muted">Chưa có thông báo.</div><?php endif; ?>
   <div class="mt-3"><?php echo e($notifications->links()); ?></div>
  </div></div></div>
 </div>
</div></div></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/operation-center/index.blade.php ENDPATH**/ ?>