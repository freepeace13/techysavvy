@props(['icon', 'name', 'description', 'url'])

<a href="{{ $url }}" class="group flex flex-col gap-2 rounded-brand border border-brand-100 bg-surface p-5 shadow-sm transition hover:border-brand-300 hover:shadow-md">
    <span class="text-2xl">{{ $icon }}</span>
    <span class="font-semibold text-ink group-hover:text-brand-600">{{ $name }}</span>
    <span class="text-sm text-ink-muted">{{ $description }}</span>
</a>
