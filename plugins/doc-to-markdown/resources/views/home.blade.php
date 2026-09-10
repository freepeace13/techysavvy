@php
    $maxKb = (int) config('doc-to-markdown.max_upload_kb');
    $maxMb = $maxKb >= 1024 ? rtrim(rtrim(number_format($maxKb / 1024, 1), '0'), '.') : null;
    $maxLabel = $maxMb ? "{$maxMb} MB" : "{$maxKb} KB";
@endphp

<x-brand::layout title="Doc to Markdown">
    <x-brand::page-header eyebrow="Convert &middot; docx &amp; pdf" title="Doc to Markdown">
        Drop a Word document or PDF, get clean Markdown back &mdash; nothing is stored, up to {{ $maxLabel }}.
        PDF conversion preserves text, not formatting.
    </x-brand::page-header>

    <div x-data="docToMarkdown({ action: '{{ route('doc-to-markdown.convert') }}' })" class="grid grid-cols-1 gap-6">
        <x-brand::panel eyebrow="Upload" title="Convert a file" :meta="$maxLabel . ' max'">
            <form
                method="POST"
                action="{{ route('doc-to-markdown.convert') }}"
                enctype="multipart/form-data"
                @submit.prevent="submitFile()"
            >
                @csrf

                <x-brand::dropzone name="file" x-show="state !== 'success'">
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
                            x-show="state === 'idle'"
                            @click="reset()"
                            class="shrink-0 text-ink-muted transition hover:text-signal-600"
                            aria-label="Remove file"
                        >&times;</button>
                    </x-slot:selected>
                </x-brand::dropzone>

                <x-brand::button
                    x-show="state !== 'success'"
                    type="submit"
                    class="mt-5 w-full"
                    ::disabled="!file || state === 'converting'"
                >
                    <span x-show="state !== 'converting'">Convert to Markdown</span>
                    <span x-show="state === 'converting'">Converting&hellip;</span>
                </x-brand::button>
            </form>

            <div x-show="state === 'error'" x-cloak class="mt-4">
                <x-brand::alert variant="error" x-text="errorMessage"></x-brand::alert>
            </div>
        </x-brand::panel>

        <x-brand::panel eyebrow="Result" title="Markdown" x-show="state === 'success'" x-cloak>
            <textarea
                readonly
                x-text="markdown"
                class="h-64 w-full resize-y rounded-brand border border-steel-200 bg-surface-muted p-3 font-mono text-xs text-ink"
            ></textarea>
            <div class="mt-4 flex items-center gap-3">
                <x-brand::button type="button" @click="download()">Download .md</x-brand::button>
                <button
                    type="button"
                    @click="reset()"
                    class="text-sm text-ink-muted underline decoration-steel-300 underline-offset-4 transition hover:text-signal-600"
                >Convert another</button>
            </div>
        </x-brand::panel>
    </div>

    @push('styles')
        <style>
            [x-cloak] { display: none !important; }
        </style>
    @endpush

    @push('scripts')
        <script>
            function docToMarkdown({ action }) {
                return {
                    state: 'idle', // idle | converting | success | error
                    dragging: false,
                    file: null,
                    markdown: '',
                    downloadName: 'converted.md',
                    errorMessage: '',

                    handleSelect(event) {
                        this.setFile(event.target.files[0] ?? null);
                    },

                    handleDrop(event) {
                        this.dragging = false;
                        this.setFile(event.dataTransfer.files[0] ?? null);
                    },

                    setFile(file) {
                        this.errorMessage = '';
                        this.file = file;
                        if (file) {
                            this.$refs.fileInput.files = this.toFileList(file);
                        }
                    },

                    toFileList(file) {
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        return dt.files;
                    },

                    async submitFile() {
                        if (!this.file || this.state === 'converting') return;

                        this.state = 'converting';

                        const formData = new FormData();
                        formData.append('file', this.file);
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                        const response = await fetch(action, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: formData,
                        });

                        const body = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            this.errorMessage = body.errors?.file?.[0] ?? body.message ?? 'That file could not be converted.';
                            this.state = 'error';
                            return;
                        }

                        this.markdown = body.markdown;
                        this.downloadName = body.filename ?? 'converted.md';
                        this.state = 'success';
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

                    reset() {
                        this.state = 'idle';
                        this.file = null;
                        this.markdown = '';
                        this.errorMessage = '';
                        this.$refs.fileInput.value = '';
                    },
                };
            }
        </script>
    @endpush
</x-brand::layout>
