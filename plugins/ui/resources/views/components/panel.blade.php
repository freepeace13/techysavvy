@props(['eyebrow' => null, 'title' => null, 'meta' => null])

<div {{ $attributes->merge(['class' => 'relative rounded-tag border border-steel-200 bg-surface p-6 shadow-sm']) }}>
    <span class="absolute left-6 top-0 h-3 w-3 -translate-y-1/2 rounded-full border-2 border-brass-500 bg-surface"></span>

    @if ($eyebrow || $title || $meta)
        <div class="mb-5 flex items-baseline justify-between border-b border-dashed border-steel-200 pb-4">
            <div>
                @if ($eyebrow)
                    <span class="font-mono text-[11px] uppercase tracking-[0.2em] text-ink-muted">{{ $eyebrow }}</span>
                @endif
                @if ($title)
                    <h2 class="font-display font-semibold text-ink">{{ $title }}</h2>
                @endif
            </div>
            @if ($meta)
                <span class="font-mono text-[11px] text-ink-muted">{{ $meta }}</span>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
