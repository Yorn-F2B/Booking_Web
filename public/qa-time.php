<?php

declare(strict_types=1);

$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('QA Time chỉ cho phép truy cập từ localhost.');
}

$projectRoot = dirname(__DIR__);
$clockFile = $projectRoot.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'qa_business_time.txt';
$timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
$message = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string) ($_POST['action'] ?? 'set');

    if ($action === 'real') {
        if (is_file($clockFile)) {
            @unlink($clockFile);
        }
        $message = 'Đã trở về GIỜ THẬT.';
    } else {
        $input = trim((string) ($_POST['datetime'] ?? ''));
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $input, $timezone);
        $errors = DateTimeImmutable::getLastErrors();
        $hasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

        if (!$parsed || $hasErrors) {
            $message = 'Thời gian không hợp lệ.';
        } else {
            if (!is_dir(dirname($clockFile))) {
                mkdir(dirname($clockFile), 0775, true);
            }
            file_put_contents($clockFile, $parsed->format('Y-m-d H:i:s').PHP_EOL, LOCK_EX);
            $message = 'Đã bật GIỜ NGHIỆP VỤ ẢO: '.$parsed->format('d/m/Y H:i');
        }
    }

    header('Location: /qa-time.php?msg='.rawurlencode($message));
    exit;
}

$activeRaw = is_file($clockFile) ? trim((string) file_get_contents($clockFile)) : '';
$active = null;
if ($activeRaw !== '') {
    try {
        $active = new DateTimeImmutable($activeRaw, $timezone);
    } catch (Throwable) {
        $active = null;
    }
}

$realNow = new DateTimeImmutable('now', $timezone);
$base = $active ?: $realNow;
$msg = (string) ($_GET['msg'] ?? '');
$inputValue = $base->format('Y-m-d\\TH:i');
$day = $base->format('Y-m-d');
$presets = [
    '13:50' => $day.'T13:50',
    '14:05' => $day.'T14:05',
    '17:59' => $day.'T17:59',
    '18:01' => $day.'T18:01',
    '20:00' => $day.'T20:00',
    '23:30' => $day.'T23:30',
    '+1 ngày' => $base->modify('+1 day')->format('Y-m-d\\TH:i'),
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QA Business Time</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f5f7fb;color:#14213d;margin:0;padding:32px}
        .box{max-width:720px;margin:auto;background:white;border:1px solid #dbe2ea;border-radius:14px;padding:24px;box-shadow:0 8px 28px #0000000d}
        h1{margin:0 0 12px;font-size:24px}.status{padding:14px;border-radius:10px;background:#eef4ff;margin:14px 0}.active{background:#fff4df}
        input{width:100%;box-sizing:border-box;padding:12px;border:1px solid #cfd8e3;border-radius:8px;font-size:16px;margin:8px 0 12px}
        button{cursor:pointer;border:0;border-radius:8px;padding:11px 14px;font-weight:700}.set{background:#0b1f3a;color:#fff}.real{background:#e8edf3;color:#172033}
        .presets{display:flex;flex-wrap:wrap;gap:8px;margin:14px 0}.preset{background:#f0f4f8;color:#172033}.msg{color:#087443;font-weight:700;margin:10px 0}
        .note{font-size:13px;color:#687386;line-height:1.5;margin-top:16px}
    </style>
</head>
<body>
<div class="box">
    <h1>Giờ nghiệp vụ ảo — QA</h1>
    <?php if ($msg !== ''): ?><div class="msg"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <div class="status <?= $active ? 'active' : '' ?>">
        <strong>Windows/Chrome:</strong> <?= $realNow->format('d/m/Y H:i:s') ?> — luôn là giờ thật<br>
        <strong>Laravel nghiệp vụ:</strong> <?= $active ? $active->format('d/m/Y H:i:s').' (ẢO)' : 'GIỜ THẬT' ?>
    </div>

    <form method="post">
        <label><strong>Chọn thời gian nghiệp vụ:</strong></label>
        <input id="datetime" type="datetime-local" name="datetime" value="<?= htmlspecialchars($inputValue, ENT_QUOTES, 'UTF-8') ?>" required>
        <button class="set" type="submit" name="action" value="set">Áp dụng giờ ảo</button>
        <button class="real" type="submit" name="action" value="real" formnovalidate>Trở về giờ thật</button>
    </form>

    <div class="presets">
        <?php foreach ($presets as $label => $value): ?>
            <button class="preset" type="button" data-value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></button>
        <?php endforeach; ?>
    </div>

    <div class="note">
        Mở trang này ở một tab riêng. Sau khi đổi giờ, chỉ cần refresh trang Booking ở tab khác.
        Session/CSRF vẫn dùng giờ Windows thật nên không cần logout và không gây 419 do nhảy giờ hệ thống.
        Scheduler/Artisan cũng đọc cùng giờ ảo khi chạy command.
    </div>
</div>
<script>
document.querySelectorAll('[data-value]').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById('datetime').value = button.dataset.value;
    });
});
</script>
</body>
</html>
