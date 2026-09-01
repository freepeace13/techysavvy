@props(['variant' => 'notice'])

@php
    $variants = [
        'notice' => 'border-brass-300 bg-brass-100 text-ink',
        'error' => 'border-signal-300 bg-signal-50 text-signal-700',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-tag border p-3 text-sm ' . ($variants[$variant] ?? $variants['notice'])]) }}>
    {{ $slot }}
</div>
