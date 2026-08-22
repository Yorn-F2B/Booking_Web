@props(['status'])

@if ($status)
    <div data-flash-inline {{ $attributes->merge(['class' => 'font-medium text-sm text-green-600']) }}>
        {{ $status }}
    </div>
@endif
