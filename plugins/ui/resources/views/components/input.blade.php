@props(['label' => null, 'name', 'error' => null, 'mono' => false])

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label for="{{ $name }}" class="text-sm font-medium text-ink">{{ $label }}</label>
    @endif

    <input {{ $attributes->merge([
        'id' => $name,
        'name' => $name,
        'class' => 'rounded-brand border border-steel-300 bg-surface px-3 py-2 text-ink placeholder:text-ink-muted focus:border-signal-500 focus:outline-none focus:ring-2 focus:ring-signal-100 '
            . ($mono ? 'font-mono' : ''),
    ]) }}>

    @error($name)
        <p class="font-mono text-xs text-signal-600">{{ $message }}</p>
    @enderror
</div>
