<?php if(isset($errors) && $errors->any()): ?>
    <script id="globalValidationErrors" type="application/json"><?php echo json_encode($errors->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\booking-web\resources\views/partials/global-validation-errors.blade.php ENDPATH**/ ?>