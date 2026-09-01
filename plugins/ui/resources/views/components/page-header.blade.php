@props(['eyebrow' => null, 'title'])

<div {{ $attributes->merge(['class' => 'mb-10 flex flex-col gap-2 border-b border-dashed border-steel-200 pb-8']) }}>
    @if ($eyebrow)
        <span class="font-mono text-xs uppercase tracking-[0.2em] text-signal-600">{{ $eyebrow }}</span>
    @endif

    <h1 class="font-display text-3xl font-bold text-ink">{{ $title }}</h1>

    @if ($slot->isNotEmpty())
        <p class="max-w-xl text-sm text-ink-muted">{{ $slot }}</p>
    @endif
</div>
