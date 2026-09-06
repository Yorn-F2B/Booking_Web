<?php $__env->startSection('title','Trung tâm công việc'); ?>
<?php $__env->startSection('content'); ?>
<style>
 .work-column{border:0;border-radius:16px;box-shadow:0 6px 24px rgba(15,23,42,.06);height:100%}
 .work-column .card-body{padding:18px}
 .work-column-head{display:flex;align-items:center;gap:10px;margin-bottom:4px}
 .work-column-head .count{min-width:30px;text-align:center}
 .work-group{border:1px solid #e3e8ef;border-radius:14px;margin-top:10px;background:#fff;overflow:hidden}
 .work-group summary{display:flex;align-items:center;gap:10px;padding:14px;cursor:pointer;list-style:none}
 .work-group summary::-webkit-details-marker{display:none}
 .work-group[open] summary{background:#f7f9fc;border-bottom:1px solid #e3e8ef}
 .work-group-items{padding:8px 12px 12px}
 .work-item{display:flex;text-decoration:none;color:#172033;border-bottom:1px solid #edf0f4;padding:11px 4px;gap:12px;align-items:start}
 .work-item:last-child{border-bottom:0}
 .work-item:hover{color:#0d6efd}
 .work-count{min-width:32px;text-align:center}
 .urgent-column{border-top:4px solid #dc3545}
 .normal-column{border-top:4px solid #0d6efd}
 .empty-work{border-radius:12px;padding:16px;margin-top:14px}
</style>
<div class="admin-wrapper"><div class="admin-content">
<div class="container-fluid py-3">
 <div class="mb-3">
  <h1 class="h3 fw-bold mb-1">Trung tâm công việc</h1>
  <div class="text-muted">Công việc đang mở được tách thành việc gấp và việc thông thường để xử lý theo đúng mức ưu tiên.</div>
 </div>

 <div id="operationCenterRealtimeFragment">
 <div class="row g-3 align-items-stretch">
  <div class="col-xl-6">
   <div class="card work-column urgent-column">
    <div class="card-body">
     <div class="work-column-head">
      <h2 class="h5 fw-bold mb-0">Việc gấp</h2>
      <span class="badge bg-danger count"><?php echo e($urgentTasks->count()); ?></span>
     </div>
     <div class="small text-muted">Các việc cần ưu tiên ngay: khách đang chờ, phòng cần dọn gấp, sự cố, quá hạn hoặc nghiệp vụ có rủi ro.</div>

     <?php $__empty_1 = true; $__currentLoopData = $urgentTaskGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <details class="work-group" data-work-group-key="urgent-<?php echo e($group['type'] ?? $loop->index); ?>">
       <summary>
        <span class="badge bg-danger">Gấp</span>
        <strong class="flex-grow-1"><?php echo e($group['title']); ?></strong>
        <span class="badge bg-danger-subtle text-danger work-count"><?php echo e($group['count']); ?></span>
        <i class="bx bx-chevron-down fs-5"></i>
       </summary>
       <div class="work-group-items">
        <?php $__currentLoopData = $group['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $task): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
         <a href="<?php echo e($task['url']); ?>" class="work-item">
          <div class="flex-grow-1">
           <div class="fw-semibold"><?php echo e($task['detail']); ?></div>
           <div class="small text-muted"><?php echo e($task['time'] ? \Carbon\Carbon::parse($task['time'])->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') : ''); ?></div>
          </div>
          <span class="small fw-semibold">Mở <i class="bx bx-chevron-right"></i></span>
         </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
       </div>
      </details>
     <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <div class="alert alert-success empty-work mb-0">Hiện không có việc gấp cần xử lý.</div>
     <?php endif; ?>
    </div>
   </div>
  </div>

  <div class="col-xl-6">
   <div class="card work-column normal-column">
    <div class="card-body">
     <div class="work-column-head">
      <h2 class="h5 fw-bold mb-0">Việc thông thường</h2>
      <span class="badge bg-primary count"><?php echo e($normalTasks->count()); ?></span>
     </div>
     <div class="small text-muted">Các việc theo quy trình thường ngày, cần làm nhưng chưa ở mức khẩn cấp.</div>

     <?php $__empty_1 = true; $__currentLoopData = $normalTaskGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <details class="work-group" data-work-group-key="normal-<?php echo e($group['type'] ?? $loop->index); ?>">
       <summary>
        <span class="badge <?php echo e($group['priority']==='medium' ? 'bg-warning text-dark' : 'bg-secondary'); ?>">
         <?php echo e($group['priority']==='medium' ? 'Cần làm' : 'Theo dõi'); ?>

        </span>
        <strong class="flex-grow-1"><?php echo e($group['title']); ?></strong>
        <span class="badge bg-primary work-count"><?php echo e($group['count']); ?></span>
        <i class="bx bx-chevron-down fs-5"></i>
       </summary>
       <div class="work-group-items">
        <?php $__currentLoopData = $group['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $task): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
         <a href="<?php echo e($task['url']); ?>" class="work-item">
          <div class="flex-grow-1">
           <div class="fw-semibold"><?php echo e($task['detail']); ?></div>
           <div class="small text-muted"><?php echo e($task['time'] ? \Carbon\Carbon::parse($task['time'])->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') : ''); ?></div>
          </div>
          <span class="small fw-semibold">Mở <i class="bx bx-chevron-right"></i></span>
         </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
       </div>
      </details>
     <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <div class="alert alert-light border empty-work mb-0">Hiện không có việc thông thường cần xử lý.</div>
     <?php endif; ?>
    </div>
   </div>
  </div>
 </div>
 </div>
</div></div></div>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.body.setAttribute('data-realtime-local-only', '1');

    const fragmentId = 'operationCenterRealtimeFragment';
    let refreshing = false;
    let queued = false;
    let debounceTimer = null;

    const openGroups = () => new Set(
        Array.from(document.querySelectorAll(`#${fragmentId} details[data-work-group-key][open]`))
            .map((item) => item.dataset.workGroupKey)
    );

    async function refreshOperationCenter() {
        if (refreshing) { queued = true; return; }
        const current = document.getElementById(fragmentId);
        if (!current) return;

        refreshing = true;
        const opened = openGroups();
        try {
            const response = await fetch(window.location.href, {
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store',
                credentials: 'same-origin'
            });
            if (!response.ok) return;

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const incoming = doc.getElementById(fragmentId);
            if (!incoming) return;

            current.innerHTML = incoming.innerHTML;
            opened.forEach((key) => {
                const detail = Array.from(current.querySelectorAll('details[data-work-group-key]'))
                    .find((item) => item.dataset.workGroupKey === key);
                if (detail) detail.open = true;
            });
        } catch (error) {
            console.warn('Không thể cập nhật Trung tâm công việc realtime.', error);
        } finally {
            refreshing = false;
            if (queued) { queued = false; refreshOperationCenter(); }
        }
    }

    function scheduleRefresh() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(refreshOperationCenter, 350);
    }

    if (window.Echo) {
        window.Echo.private('admin.realtime')
            .listen('.app.updated', scheduleRefresh);
    }

    // Các mốc "quá hạn / sắp đến giờ" có thể đổi chỉ theo thời gian mà không có DB event.
    // Poll 5 giây là lớp dự phòng khi Reverb tạm ngắt; bình thường event vẫn refresh gần như ngay lập tức.
    setInterval(() => {
        if (!document.hidden) refreshOperationCenter();
    }, 5000);

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) scheduleRefresh();
    });
    window.addEventListener('online', scheduleRefresh);
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/operation-center/index.blade.php ENDPATH**/ ?>