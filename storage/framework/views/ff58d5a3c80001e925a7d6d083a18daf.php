<?php $__env->startSection('title','Trung tâm công việc'); ?>
<?php $__env->startSection('content'); ?>
<style>
 .work-group{border:1px solid #e3e8ef;border-radius:14px;margin-top:10px;background:#fff;overflow:hidden}.work-group summary{display:flex;align-items:center;gap:10px;padding:14px;cursor:pointer;list-style:none}.work-group summary::-webkit-details-marker{display:none}.work-group[open] summary{background:#f7f9fc;border-bottom:1px solid #e3e8ef}.work-group-items{padding:8px 12px 12px}.work-item{display:flex;text-decoration:none;color:#172033;border-bottom:1px solid #edf0f4;padding:11px 4px;gap:12px;align-items:start}.work-item:last-child{border-bottom:0}.work-item:hover{color:#0d6efd}.work-count{min-width:32px;text-align:center}
</style>
<div class="admin-wrapper"><div class="admin-content">
<div class="container-fluid py-3">
 <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
  <div><h1 class="h3 fw-bold mb-1">Trung tâm công việc</h1><div class="text-muted">Các việc đang còn mở được hệ thống tổng hợp theo vai trò; không cần mở từng booking để dò.</div></div>
  <form method="POST" action="<?php echo e(route('admin.notifications.read-all')); ?>"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?><button class="btn btn-outline-secondary btn-sm">Đánh dấu thông báo đã đọc</button></form>
 </div>
 <div class="row g-3">
  <div class="col-xl-7"><div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h5 fw-bold">Việc cần xử lý <span class="badge bg-danger-subtle text-danger"><?php echo e($tasks->count()); ?></span></h2>
   <div class="small text-muted">Mỗi nhóm chỉ hiện một dòng. Mở nhóm để xem các booking/phòng cụ thể.</div>
   <?php $__empty_1 = true; $__currentLoopData = $taskGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <details class="work-group">
     <summary>
      <span class="badge <?php echo e($group['priority']==='high'?'bg-danger':($group['priority']==='medium'?'bg-warning text-dark':'bg-secondary')); ?>"><?php echo e($group['priority']==='high'?'Gấp':($group['priority']==='medium'?'Cần làm':'Theo dõi')); ?></span>
      <strong class="flex-grow-1"><?php echo e($group['title']); ?></strong><span class="badge bg-primary work-count"><?php echo e($group['count']); ?></span><i class="bx bx-chevron-down fs-5"></i>
     </summary>
     <div class="work-group-items">
      <?php $__currentLoopData = $group['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $task): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><a href="<?php echo e($task['url']); ?>" class="work-item"><div class="flex-grow-1"><div class="fw-semibold"><?php echo e($task['detail']); ?></div><div class="small text-muted"><?php echo e($task['time'] ? \Carbon\Carbon::parse($task['time'])->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') : ''); ?></div></div><span class="small fw-semibold">Mở <i class="bx bx-chevron-right"></i></span></a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
     </div>
    </details>
   <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div class="alert alert-success mt-3 mb-0">Hiện không có công việc tồn cần xử lý.</div><?php endif; ?>
  </div></div></div>
  <div class="col-xl-5"><div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h5 fw-bold">Thông báo</h2>
   <?php $__empty_1 = true; $__currentLoopData = $notificationGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <details class="work-group">
     <summary><strong class="flex-grow-1"><?php echo e($group['title']); ?></strong><span class="badge <?php echo e($group['unread_count'] ? 'bg-danger' : 'bg-secondary'); ?> work-count"><?php echo e($group['count']); ?></span><i class="bx bx-chevron-down fs-5"></i></summary>
     <div class="work-group-items"><?php $__currentLoopData = $group['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><a href="<?php echo e(route('admin.notifications.open',$n)); ?>" class="work-item <?php echo e($n->read_at?'opacity-75':''); ?>"><div class="flex-grow-1"><div><?php echo e($n->message); ?></div><div class="small text-muted mt-1"><?php echo e($n->created_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y')); ?></div></div><?php if(!$n->read_at): ?><span class="badge bg-danger rounded-pill">Mới</span><?php else: ?><i class="bx bx-chevron-right"></i><?php endif; ?></a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
    </details>
   <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div class="text-muted mt-3">Chưa có thông báo.</div><?php endif; ?>
   <div class="mt-3"><?php echo e($notifications->links()); ?></div>
  </div></div></div>
 </div>
</div></div></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/operation-center/index.blade.php ENDPATH**/ ?>