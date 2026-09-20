@pluginAssets('env-diff')

<x-ui::layout title="EnvDiff">
    <x-ui::page-header eyebrow="Config check &middot; runs in your browser" title="EnvDiff">
        Paste two .env files to see missing and extra keys, empty or placeholder values, and
        likely real secrets &mdash; then copy a clean .env.example.
    </x-ui::page-header>

    <x-ui::alert class="mb-6" data-testid="env-diff-safety-note">
        <strong>Why it is safe to paste your env here</strong>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Everything is parsed by JavaScript in your own browser. Your input is never sent to our server.</li>
            <li>Nothing is stored, logged, or written to your browser's local storage.</li>
            <li>This page loads no third-party scripts, analytics, or trackers.</li>
            <li>Don't take our word for it: open your browser's Network tab, paste something, and watch that no request is made.</li>
            <li>Still, avoid pasting live production secrets on a shared or untrusted machine.</li>
        </ul>
    </x-ui::alert>

    <div x-data="envDiff()">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <x-ui::panel eyebrow="File A" title="Your .env">
                <textarea x-model="a" rows="12" spellcheck="false" autocomplete="off" aria-label="Your .env contents"
                    placeholder="APP_NAME=My App&#10;APP_KEY=&#10;DB_PASSWORD=..."
                    class="w-full rounded-brand border border-steel-300 bg-surface px-3 py-2 font-mono text-sm text-ink placeholder:text-ink-muted focus:border-signal-500 focus:outline-none focus:ring-2 focus:ring-signal-100"></textarea>
            </x-ui::panel>

            <x-ui::panel eyebrow="File B" title="Your .env.example">
                <textarea x-model="b" rows="12" spellcheck="false" autocomplete="off" aria-label="Your .env.example contents"
                    placeholder="APP_NAME=&#10;APP_KEY=&#10;DB_PASSWORD="
                    class="w-full rounded-brand border border-steel-300 bg-surface px-3 py-2 font-mono text-sm text-ink placeholder:text-ink-muted focus:border-signal-500 focus:outline-none focus:ring-2 focus:ring-signal-100"></textarea>
            </x-ui::panel>
        </div>

        <div class="mt-3 flex justify-end">
            <x-ui::button type="button" variant="ghost" @click="clear()">Clear both</x-ui::button>
        </div>

        <template x-if="result">
            <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                <x-ui::panel eyebrow="Drift" title="Key differences">
                    <div class="space-y-4 text-sm">
                        <div>
                            <h3 class="font-medium text-ink">In A, missing from B <span class="font-mono text-ink-muted" x-text="'(' + result.diff.onlyInA.length + ')'"></span></h3>
                            <p x-show="!result.diff.onlyInA.length" class="text-ink-muted">None.</p>
                            <ul class="mt-1 flex flex-wrap gap-1.5">
                                <template x-for="key in result.diff.onlyInA" :key="key"><li class="rounded-tag border border-brass-300 bg-brass-100 px-2 py-0.5 font-mono text-xs" x-text="key"></li></template>
                            </ul>
                        </div>
                        <div>
                            <h3 class="font-medium text-ink">In B, missing from A <span class="font-mono text-ink-muted" x-text="'(' + result.diff.onlyInB.length + ')'"></span></h3>
                            <p x-show="!result.diff.onlyInB.length" class="text-ink-muted">None.</p>
                            <ul class="mt-1 flex flex-wrap gap-1.5">
                                <template x-for="key in result.diff.onlyInB" :key="key"><li class="rounded-tag border border-brass-300 bg-brass-100 px-2 py-0.5 font-mono text-xs" x-text="key"></li></template>
                            </ul>
                        </div>
                        <p class="font-mono text-xs text-ink-muted" x-text="result.diff.inBoth.length + ' keys in both'"></p>
                    </div>
                </x-ui::panel>

                <x-ui::panel eyebrow="Checks" title="Values worth a look">
                    <div class="space-y-5 text-sm">
                        <template x-for="[label, r] in [['File A', result.a], ['File B', result.b]]" :key="label">
                            <div>
                                <h3 class="font-medium text-ink" x-text="label + ' · ' + r.count + ' keys'"></h3>
                                <p x-show="!r.secrets.length && !r.empty.length && !r.placeholders.length && !r.duplicates.length && !r.invalid.length" class="text-ink-muted">Nothing suspicious found.</p>
                                <ul class="mt-1 space-y-1">
                                    <template x-for="s in r.secrets" :key="'s' + s.key">
                                        <li class="rounded-tag border border-signal-300 bg-signal-50 px-2 py-1 text-signal-700">
                                            <span class="font-mono text-xs" x-text="s.key"></span>
                                            <span x-text="'— likely secret: ' + s.reason"></span>
                                            <span class="font-mono text-xs" x-text="s.preview"></span>
                                        </li>
                                    </template>
                                    <li x-show="r.placeholders.length" class="text-ink"><span class="text-ink-muted">Placeholder values:</span> <span class="font-mono text-xs" x-text="r.placeholders.join(', ')"></span></li>
                                    <li x-show="r.empty.length" class="text-ink"><span class="text-ink-muted">Empty values:</span> <span class="font-mono text-xs" x-text="r.empty.join(', ')"></span></li>
                                    <li x-show="r.duplicates.length" class="text-ink"><span class="text-ink-muted">Duplicate keys:</span> <span class="font-mono text-xs" x-text="r.duplicates.join(', ')"></span></li>
                                    <template x-for="i in r.invalid" :key="'i' + i.line">
                                        <li class="text-ink"><span class="text-ink-muted" x-text="'Line ' + i.line + ' isn’t KEY=value:'"></span> <span class="font-mono text-xs">(skipped)</span></li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                    </div>
                </x-ui::panel>

                <x-ui::panel eyebrow="Output" title="Generated .env.example" class="md:col-span-2">
                    <textarea x-ref="example" x-model="example" readonly rows="8" aria-label="Generated .env.example"
                        class="w-full rounded-brand border border-steel-300 bg-surface px-3 py-2 font-mono text-sm text-ink"></textarea>
                    <div class="mt-3 flex items-center gap-3">
                        <x-ui::button type="button" variant="secondary" @click="copyExample()"><span x-text="copied ? 'Copied' : 'Copy'"></span></x-ui::button>
                        <p class="font-mono text-xs text-ink-muted">Built from File A. Values are stripped; comments and order are kept.</p>
                    </div>
                </x-ui::panel>
            </div>
        </template>
    </div>
</x-ui::layout>
