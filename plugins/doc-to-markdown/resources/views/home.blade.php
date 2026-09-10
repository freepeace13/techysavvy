@php
    $maxKb = (int) config('doc-to-markdown.max_upload_kb');
    $maxMb = $maxKb >= 1024 ? rtrim(rtrim(number_format($maxKb / 1024, 1), '0'), '.') : null;
    $maxLabel = $maxMb ? "{$maxMb} MB" : "{$maxKb} KB";

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

    <div x-data="docToMarkdown({ action: '{{ route('doc-to-markdown.convert') }}' })" class="grid grid-cols-1 gap-6">
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
                    <div class="flex items-center justify-between font-mono text-xs text-ink">
                        <span class="truncate" x-text="file?.name"></span>
                        <span x-text="progress + '%'"></span>
                    </div>
                    <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-surface-muted">
                        <div
                            class="h-full rounded-full bg-signal-500 transition-[width] duration-150"
                            :style="`width: ${progress}%`"
                        ></div>
                    </div>
                </div>
            </div>

            <div x-show="state === 'error'" x-cloak class="mt-4">
                <x-ui::alert variant="error" x-text="errorMessage"></x-ui::alert>
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
                    <span x-text="copied ? 'Copied' : 'Copy'"></span>
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
        <style>
            [x-cloak] { display: none !important; }

            /*
             * Tailwind's preflight resets headings to font-size/font-weight
             * inherit and links to color/text-decoration inherit, so markdown-it
             * output renders as undifferentiated plain text until these rules
             * put the document semantics back. markdown-it ships no CSS of its
             * own — it only emits HTML — so this block is the whole stylesheet
             * for a rendered document.
             */
            .markdown-render h1, .markdown-render h2, .markdown-render h3,
            .markdown-render h4, .markdown-render h5, .markdown-render h6 {
                font-family: var(--font-display, inherit);
                font-weight: 600;
                line-height: 1.25;
                margin: 1.4em 0 0.5em;
                text-wrap: balance;
            }
            .markdown-render h1 { font-size: 1.85em; letter-spacing: -0.02em; }
            .markdown-render h2 { font-size: 1.45em; letter-spacing: -0.01em; }
            .markdown-render h3 { font-size: 1.2em; }
            .markdown-render h4 { font-size: 1.05em; }
            .markdown-render h5 { font-size: 1em; }
            .markdown-render h6 { font-size: 0.9em; color: var(--color-ink-muted, #5B6664); }

            .markdown-render h1:first-child, .markdown-render h2:first-child,
            .markdown-render h3:first-child { margin-top: 0; }

            .markdown-render h1, .markdown-render h2 {
                padding-bottom: 0.25em;
                border-bottom: 1px solid var(--color-steel-200, #e4e7ec);
            }

            .markdown-render p { margin: 0.75em 0; }
            .markdown-render :is(p, li, blockquote) { overflow-wrap: anywhere; }

            .markdown-render a {
                color: var(--color-signal-600, #B93D0C);
                text-decoration: underline;
                text-underline-offset: 2px;
            }
            .markdown-render a:hover { color: var(--color-signal-700, #92300A); }

            .markdown-render strong { font-weight: 600; }
            .markdown-render em { font-style: italic; }

            .markdown-render blockquote {
                margin: 0.75em 0;
                padding: 0.1em 0 0.1em 1em;
                border-left: 3px solid var(--color-steel-300, #d0d5dd);
                color: var(--color-ink-muted, #5B6664);
            }

            .markdown-render code {
                font-family: var(--font-mono, ui-monospace, monospace);
                font-size: 0.9em;
                background: var(--color-surface, #fff);
                border: 1px solid var(--color-steel-200, #e4e7ec);
                border-radius: 0.25rem;
                padding: 0.1em 0.35em;
            }
            .markdown-render pre {
                margin: 0.75em 0;
                padding: 0.75em 1em;
                background: var(--color-surface, #fff);
                border: 1px solid var(--color-steel-200, #e4e7ec);
                border-radius: 0.5rem;
                overflow-x: auto;
            }
            /* A fenced block is <pre><code>; the inline chrome must not repeat. */
            .markdown-render pre code {
                background: none;
                border: 0;
                border-radius: 0;
                padding: 0;
                font-size: 0.875em;
            }

            .markdown-render img { max-width: 100%; height: auto; }
            .markdown-render ul, .markdown-render ol { margin: 0.75em 0; padding-left: 1.5em; }
            .markdown-render li { margin: 0.25em 0; }
            .markdown-render hr { margin: 1.5em 0; border: none; border-top: 1px dashed var(--color-steel-300, #d0d5dd); }
            .markdown-render table { border-collapse: collapse; margin: 0.75em 0; width: 100%; }
            .markdown-render th, .markdown-render td {
                border: 1px solid var(--color-steel-200, #e4e7ec);
                padding: 0.4em 0.6em;
                text-align: left;
            }
            .markdown-render th { background: var(--color-surface, #fff); font-weight: 600; }
        </style>
    @endpush

    @push('scripts')
        <script src="{{ $bundleUrl }}"></script>

        <script>
            function docToMarkdown({ action }) {
                return {
                    state: 'idle', // idle | uploading | success | error
                    dragging: false,
                    file: null,
                    progress: 0,
                    markdown: '',
                    markdownHtml: '',
                    downloadName: 'converted.md',
                    errorMessage: '',
                    view: 'markdown', // markdown | raw
                    copied: false,

                    handleSelect(event) {
                        this.setFile(event.target.files[0] ?? null);
                    },

                    handleDrop(event) {
                        this.dragging = false;
                        this.setFile(event.dataTransfer.files[0] ?? null);
                    },

                    setFile(file) {
                        if (!file) return;

                        this.errorMessage = '';
                        this.file = file;
                        this.$refs.fileInput.files = this.toFileList(file);
                        this.uploadFile();
                    },

                    toFileList(file) {
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        return dt.files;
                    },

                    uploadFile() {
                        if (!this.file) return;

                        this.state = 'uploading';
                        this.progress = 0;

                        const formData = new FormData();
                        formData.append('file', this.file);
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', action);
                        xhr.responseType = 'json';
                        xhr.setRequestHeader('Accept', 'application/json');

                        xhr.upload.addEventListener('progress', (event) => {
                            if (event.lengthComputable) {
                                this.progress = Math.round((event.loaded / event.total) * 100);
                            }
                        });

                        xhr.addEventListener('load', () => {
                            const body = xhr.response ?? {};

                            if (xhr.status < 200 || xhr.status >= 300) {
                                this.errorMessage = body.errors?.file?.[0] ?? body.message ?? 'That file could not be converted.';
                                this.state = 'error';
                                return;
                            }

                            this.markdown = body.markdown;
                            this.markdownHtml = window.docToMarkdownRender(body.markdown ?? '');
                            this.downloadName = body.filename ?? 'converted.md';
                            this.state = 'success';
                        });

                        xhr.addEventListener('error', () => {
                            this.errorMessage = 'That file could not be converted.';
                            this.state = 'error';
                        });

                        xhr.send(formData);
                    },

                    download() {
                        const blob = new Blob([this.markdown], { type: 'text/markdown' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = this.downloadName;
                        a.click();
                        URL.revokeObjectURL(url);
                    },

                    copy() {
                        const markedCopied = () => {
                            this.copied = true;
                            setTimeout(() => { this.copied = false; }, 2000);
                        };

                        // Write both text/html and text/plain so pasting into a rich-text
                        // target (email, docs, chat) renders actual headings/bullets/bold
                        // instead of literal markdown syntax; plain-text targets still get
                        // the markdown source. Falls back to plain text where the async
                        // clipboard item API (or its html support) isn't available.
                        if (window.ClipboardItem) {
                            const item = new ClipboardItem({
                                'text/html': new Blob([this.markdownHtml], { type: 'text/html' }),
                                'text/plain': new Blob([this.markdown], { type: 'text/plain' }),
                            });

                            navigator.clipboard.write([item]).then(markedCopied, () => {
                                navigator.clipboard.writeText(this.markdown).then(markedCopied);
                            });

                            return;
                        }

                        navigator.clipboard.writeText(this.markdown).then(markedCopied);
                    },

                    reset() {
                        this.state = 'idle';
                        this.file = null;
                        this.progress = 0;
                        this.markdown = '';
                        this.markdownHtml = '';
                        this.errorMessage = '';
                        this.view = 'markdown';
                        this.copied = false;
                        this.$refs.fileInput.value = '';
                    },
                };
            }
        </script>
    @endpush
</x-ui::layout>
