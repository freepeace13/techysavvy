@php
    use Techysavvy\DocToMarkdown\Support\UploadLimit;

    $maxLabel = UploadLimit::label();
    $maxBytes = UploadLimit::kilobytes() * 1024;

    // The published bundle lives at a stable, unhashed URL (unlike host/'s
    // Vite output, which is content-hashed), so a rebuild would otherwise
    // keep being served from browser cache. Fingerprint it by contents:
    // the URL changes only when the bundle actually changes.
    $bundle = 'vendor/doc-to-markdown/doc-to-markdown.js';
    $bundlePath = public_path($bundle);
    $bundleUrl = asset($bundle);

    if (is_file($bundlePath)) {
        $bundleUrl .= '?id='.substr(hash_file('xxh128', $bundlePath), 0, 12);
    }
@endphp

<x-ui::layout title="Doc to Markdown">
    <x-ui::page-header eyebrow="Convert &middot; docx &amp; pdf" title="Doc to Markdown">
        Drop a Word document or PDF, get clean Markdown back &mdash; nothing is stored, up to {{ $maxLabel }}.
        PDF conversion preserves text, not formatting.
    </x-ui::page-header>

    <div x-data="docToMarkdown({ action: @js(route('doc-to-markdown.convert')), maxBytes: @js($maxBytes) })" class="grid grid-cols-1 gap-6">
        <p class="sr-only" role="status" aria-live="polite" x-text="status"></p>

        <x-ui::panel
            eyebrow="Upload"
            title="Convert a file"
            :meta="$maxLabel . ' max'"
            x-show="state !== 'success'"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 -translate-x-4"
        >
            <div>
                <x-ui::dropzone
                    name="file"
                    accept=".docx,.pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/pdf"
                    idle-expr="state === 'idle' || state === 'error'"
                    x-show="state !== 'uploading'"
                >
                    <svg viewBox="0 0 48 48" fill="none" class="h-10 w-10 text-steel-300" aria-hidden="true">
                        <path d="M14 6h14l10 10v26H14V6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        <path d="M28 6v10h10" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    </svg>
                    <p class="text-sm font-medium text-ink">Drop a .docx or .pdf here</p>
                    <p class="font-mono text-xs text-ink-muted">or click to browse</p>

                    <x-slot:selected>
                        <span class="truncate" x-text="file.name"></span>
                        <button
                            type="button"
                            @click="reset()"
                            class="shrink-0 text-ink-muted transition hover:text-signal-600"
                            aria-label="Remove file"
                        >&times;</button>
                    </x-slot:selected>
                </x-ui::dropzone>

                <div x-show="state === 'uploading'" x-cloak class="rounded-brand border-2 border-dashed border-steel-300 px-4 py-8">
                    <div class="flex items-center justify-between gap-3 font-mono text-xs text-ink">
                        <span class="truncate" x-text="file?.name"></span>
                        <span x-text="converting ? 'Converting…' : progress + '%'"></span>
                    </div>
                    <div
                        class="mt-3 h-2 w-full overflow-hidden rounded-full bg-surface-muted"
                        role="progressbar"
                        aria-label="Upload progress"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        :aria-valuenow="progress"
                        :aria-valuetext="converting ? 'Converting' : progress + '%'"
                    >
                        <div
                            class="h-full rounded-full bg-signal-500 transition-[width] duration-150"
                            :class="converting && 'animate-pulse'"
                            :style="`width: ${progress}%`"
                        ></div>
                    </div>
                    <button
                        type="button"
                        @click="cancel()"
                        class="mt-4 text-sm text-ink-muted underline decoration-steel-300 underline-offset-4 transition hover:text-signal-600"
                    >Cancel</button>
                </div>
            </div>

            <div x-show="state === 'error'" x-cloak class="mt-4">
                <x-ui::alert variant="error" role="alert" x-text="errorMessage"></x-ui::alert>
            </div>
        </x-ui::panel>

        <x-ui::panel
            eyebrow="Result"
            title="Markdown"
            class="min-w-0"
            x-show="state === 'success'"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-4"
        >
            <div class="flex items-center justify-between gap-3">
                <x-ui::tabs
                    model="view"
                    :options="[
                        ['value' => 'markdown', 'label' => 'Markdown'],
                        ['value' => 'raw', 'label' => 'Raw'],
                    ]"
                />

                <x-ui::button type="button" variant="secondary" @click="copy()">
                    <span x-text="copied ? 'Copied' : (copyFailed ? 'Copy failed' : 'Copy')"></span>
                </x-ui::button>
            </div>

            <div class="mt-3">
                <div class="markdown-render rounded-brand border border-steel-200 bg-surface-muted p-5 text-sm text-ink" x-show="view === 'markdown'" x-html="markdownHtml"></div>
                <pre class="rounded-brand border border-steel-200 bg-surface-muted p-5 font-mono text-xs text-ink whitespace-pre-wrap [overflow-wrap:anywhere] max-w-full" x-show="view === 'raw'" x-cloak x-text="markdown"></pre>
            </div>

            <div class="mt-4 flex items-center gap-3">
                <x-ui::button type="button" @click="download()">Download .md</x-ui::button>
                <button
                    type="button"
                    @click="reset()"
                    class="text-sm text-ink-muted underline decoration-steel-300 underline-offset-4 transition hover:text-signal-600"
                >Convert another</button>
            </div>
        </x-ui::panel>
    </div>

    @push('styles')
        @include('doc-to-markdown::partials.markdown-styles')
    @endpush

    @push('scripts')
        <script src="{{ $bundleUrl }}"></script>

        <script>
            // If the bundle is missing (not built/published), fail visibly
            // instead of leaving the page frozen.
            if (typeof window.docToMarkdown !== 'function') {
                window.docToMarkdown = () => ({
                    state: 'error', dragging: false, file: null, progress: 0, converting: false,
                    markdown: '', markdownHtml: '', view: 'markdown', copied: false, copyFailed: false, status: '',
                    errorMessage: 'The converter script failed to load. Reload the page, or ask the site owner to run the build.',
                    handleSelect() {}, handleDrop() {}, reset() {}, cancel() {}, copy() {}, download() {},
                });
            }
        </script>
    @endpush
</x-ui::layout>
