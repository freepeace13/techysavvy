<x-brand::layout title="Tools">
    <h1 class="mb-6 text-2xl font-bold text-ink">Tools</h1>

    <x-brand::tool-grid>
        @forelse ($tools as $tool)
            <x-brand::tool-card
                :icon="$tool->icon()"
                :name="$tool->name()"
                :description="$tool->description()"
                :url="$tool->url()"
            />
        @empty
            <p>No tools installed yet.</p>
        @endforelse
    </x-brand::tool-grid>
</x-brand::layout>
