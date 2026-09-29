<x-ui::layout title="Webhook Inspector">
    <x-ui::page-header eyebrow="Debug bench" title="Webhook Inspector">
        Get a throwaway URL, point any webhook sender at it, and watch each request arrive live &mdash;
        method, headers, query and body.
    </x-ui::page-header>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 md:items-start">
        <x-ui::panel eyebrow="How it works" title="Three steps">
            <ol class="flex list-decimal flex-col gap-2 pl-5 text-sm text-ink">
                <li>Create a bin. You get a capture URL and a private viewer page.</li>
                <li>Paste the capture URL into Stripe, GitHub, Slack &mdash; or <code class="font-mono">curl</code> it.</li>
                <li>Watch requests appear on the viewer page within a couple of seconds.</li>
            </ol>
        </x-ui::panel>

        <x-ui::panel eyebrow="Start" title="New bin">
            <form method="POST" action="{{ route('webhook-inspector.bins.store') }}" class="flex flex-col gap-4">
                @csrf
                <x-ui::button>Create bin</x-ui::button>
            </form>

            <x-ui::alert class="mt-4">
                Anyone with a bin's viewer link can read what it captured. Bins are deleted after
                {{ config('webhook-inspector.lifespan_hours') }} hours. {{ "Don't send production secrets." }}
            </x-ui::alert>
        </x-ui::panel>
    </div>
</x-ui::layout>
