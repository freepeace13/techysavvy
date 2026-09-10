<x-ui::layout title="Tools">
    <h1 class="mb-6 font-display text-2xl font-bold text-ink">Tools</h1>

    <x-ui::tool-grid>
        @forelse ($tools as $tool)
            <x-ui::tool-card
                :icon="$tool->icon()"
                :name="$tool->name()"
                :description="$tool->description()"
                :url="$tool->url()"
            />
        @empty
            <p>No tools installed yet.</p>
        @endforelse
    </x-ui::tool-grid>
</x-ui::layout>
