@props(['variant' => 'primary', 'as' => 'button', 'type' => 'submit'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-brand px-4 py-2 text-sm font-medium transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-signal-500 disabled:opacity-50 disabled:pointer-events-none';

    $variants = [
        'primary' => 'bg-signal-500 text-white hover:bg-signal-600',
        'secondary' => 'border border-steel-300 bg-surface text-ink hover:border-signal-300 hover:text-signal-600',
    ];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

@if ($as === 'a')
    <a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
