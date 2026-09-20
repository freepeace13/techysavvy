<x-ui::layout title="EnvDiff">
    <x-ui::page-header title="EnvDiff">
        Compare two .env files, spot drift and leaked secrets, and generate a clean .env.example. Coming soon.
    </x-ui::page-header>

    <x-ui::alert>
        <strong>Why it is safe to paste your env here</strong>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Everything is parsed by JavaScript in your own browser. Your input is never sent to our server.</li>
            <li>Nothing is stored, logged, or written to your browser's local storage.</li>
            <li>The page loads no third-party scripts, analytics, or trackers.</li>
            <li>Don't take our word for it: open your browser's Network tab, paste something, and watch that no request is made.</li>
            <li>Still, avoid pasting live production secrets on a shared or untrusted machine.</li>
        </ul>
    </x-ui::alert>
</x-ui::layout>
