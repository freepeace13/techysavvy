@props([
    'options', // [['value' => 'markdown', 'label' => 'Markdown'], ...]
    'model', // Alpine expression holding the active value, e.g. 'view'
])

<div {{ $attributes->merge(['class' => 'inline-flex rounded-brand border border-steel-300 p-0.5 text-sm font-medium']) }}>
    @foreach ($options as $option)
        <x-brand::button
            type="button"
            variant="ghost"
            @click="{{ $model }} = '{{ $option['value'] }}'"
            x-bind:class="{{ $model }} === '{{ $option['value'] }}' ? 'bg-signal-500 text-white hover:bg-signal-600' : 'hover:bg-surface-muted'"
            class="rounded-[calc(var(--radius-brand,0.5rem)-0.125rem)] px-3 py-1"
        >{{ $option['label'] }}</x-brand::button>
    @endforeach
</div>
