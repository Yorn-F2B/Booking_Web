@if(isset($errors) && $errors->any())
    <script id="globalValidationErrors" type="application/json">{!! json_encode($errors->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endif
