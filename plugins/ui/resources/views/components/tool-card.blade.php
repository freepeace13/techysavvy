@props(['icon', 'name', 'description', 'url'])

@php
    $id = 'TL-' . str_pad((int) (crc32($name) % 99) + 1, 2, '0', STR_PAD_LEFT);
@endphp

<a href="{{ $url }}" class="group relative flex flex-col gap-3 rounded-tag border border-steel-200 bg-surface p-5 pt-6 shadow-sm transition hover:-translate-y-0.5 hover:border-signal-300 hover:shadow-md">
    {{-- punch hole --}}
    <span class="absolute left-5 top-0 h-3 w-3 -translate-y-1/2 rounded-full border-2 border-brass-500 bg-surface"></span>

    <div class="flex items-start justify-between border-b border-dashed border-steel-200 pb-3">
        <span class="text-2xl leading-none">{{ $icon }}</span>
        <span class="font-mono text-[11px] tracking-wide text-ink-muted">{{ $id }}</span>
    </div>

    <div class="flex flex-col gap-1">
        <span class="font-display font-semibold text-ink group-hover:text-signal-600">{{ $name }}</span>
        <span class="text-sm text-ink-muted">{{ $description }}</span>
    </div>
</a>
