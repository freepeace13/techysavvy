@php
    $maxKb = (int) config('drop-share.max_upload_kb');
    $maxMb = $maxKb >= 1024 ? rtrim(rtrim(number_format($maxKb / 1024, 1), '0'), '.') : null;
    $maxLabel = $maxMb ? "{$maxMb} MB" : "{$maxKb} KB";

    $lifespanHours = (int) config('drop-share.lifespan_hours');
    $lifespanLabel = $lifespanHours % 24 === 0
        ? \Illuminate\Support\Str::plural('day', $lifespanHours / 24, true)
        : \Illuminate\Support\Str::plural('hour', $lifespanHours, true);
@endphp

<x-brand::layout title="Drop Share">
    <div class="mb-10 flex flex-col gap-2 border-b border-dashed border-steel-200 pb-8">
        <span class="font-mono text-xs uppercase tracking-[0.2em] text-signal-600">Package drop &middot; send &amp; receive</span>
        <h1 class="font-display text-3xl font-bold text-ink">Drop Share</h1>
        <p class="max-w-xl text-sm text-ink-muted">
            Send a file, get a claim phrase. Trade the phrase back in and it's yours &mdash;
            up to {{ $maxLabel }}, held for {{ $lifespanLabel }} before it's swept off the dock.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 md:items-start">
        {{-- DOCK A · SEND ------------------------------------------------ --}}
        <div
            x-data="dropShareUpload({
                action: '{{ route('drop-share.upload') }}',
                maxLabel: '{{ $maxLabel }}',
            })"
            class="relative rounded-tag border border-steel-200 bg-surface p-6 shadow-sm"
        >
            <span class="absolute left-6 top-0 h-3 w-3 -translate-y-1/2 rounded-full border-2 border-brass-500 bg-surface"></span>

            <div class="mb-5 flex items-baseline justify-between border-b border-dashed border-steel-200 pb-4">
                <div>
                    <span class="font-mono text-[11px] uppercase tracking-[0.2em] text-ink-muted">Dock A</span>
                    <h2 class="font-display font-semibold text-ink">Send a file</h2>
                </div>
                <span class="font-mono text-[11px] text-ink-muted">{{ $maxLabel }} max</span>
            </div>

            <form
                method="POST"
                action="{{ route('drop-share.upload') }}"
                enctype="multipart/form-data"
                @submit.prevent="submitFile()"
            >
                @csrf

                {{-- dropzone --}}
                <div
                    x-show="state !== 'success'"
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="handleDrop($event)"
                    @click="state === 'idle' && $refs.fileInput.click()"
                    :class="{
                        'border-signal-500 bg-signal-50': dragging,
                        'border-signal-300': file && state === 'idle',
                        'border-steel-300': !dragging && !(file && state === 'idle'),
                        'cursor-pointer hover:border-signal-300 hover:bg-surface-muted': state === 'idle',
                    }"
                    class="flex flex-col items-center gap-3 rounded-brand border-2 border-dashed px-4 py-8 text-center transition-colors duration-150"
                >
                    <input
                        x-ref="fileInput"
                        type="file"
                        name="file"
                        required
                        class="sr-only"
                        @change="handleSelect($event)"
                    >

                    <template x-if="!file">
                        <div class="flex flex-col items-center gap-3">
                            <svg viewBox="0 0 48 48" fill="none" class="h-10 w-10 text-steel-300" aria-hidden="true">
                                <path d="M6 16 24 7l18 9-18 9-18-9Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M6 16v16l18 9 18-9V16" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M24 25v16" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <p class="text-sm font-medium text-ink">Drop a file here</p>
                            <p class="font-mono text-xs text-ink-muted">or click to browse</p>
                        </div>
                    </template>

                    <template x-if="file">
                        <div class="flex w-full items-center justify-between gap-3 font-mono text-xs text-ink" @click.stop>
                            <span class="truncate" x-text="file.name"></span>
                            <div class="flex shrink-0 items-center gap-3">
                                <span class="text-ink-muted" x-text="formatBytes(file.size)"></span>
                                <button
                                    type="button"
                                    x-show="state === 'idle'"
                                    @click="reset()"
                                    class="text-ink-muted transition hover:text-signal-600"
                                    aria-label="Remove file"
                                >&times;</button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- progress gauge --}}
                <div x-show="state === 'uploading'" x-cloak class="mt-4">
                    <div class="mb-1.5 flex items-baseline justify-between font-mono text-xs text-ink-muted">
                        <span>Sending&hellip;</span>
                        <span x-text="progress + '%'"></span>
                    </div>
                    <div class="gauge-track relative h-2.5 overflow-hidden rounded-full bg-surface-sunken">
                        <div
                            class="gauge-fill h-full rounded-full bg-signal-500"
                            :style="`width: ${progress}%`"
                        ></div>
                    </div>
                </div>

                <button
                    x-show="state !== 'success'"
                    type="submit"
                    :disabled="!file || state === 'uploading'"
                    class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-brand bg-signal-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-signal-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-signal-500 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span x-show="state !== 'uploading'">Send it</span>
                    <span x-show="state === 'uploading'">Sending&hellip;</span>
                </button>
            </form>

            {{-- claim ticket --}}
            <div
                x-show="state === 'success'"
                x-cloak
                x-transition:enter="animate-stamp-in"
                class="relative mt-1 rounded-brand bg-surface-muted px-5 py-6 ticket-notch"
            >
                <span class="absolute left-5 top-0 h-3 w-3 -translate-y-1/2 rounded-full border-2 border-brass-500 bg-surface"></span>

                <p class="font-mono text-[11px] uppercase tracking-[0.2em] text-ink-muted">Claim phrase</p>
                <p class="mt-1 break-all font-mono text-lg font-medium text-ink" x-text="phrase"></p>
                <p class="mt-2 text-xs text-ink-muted">Held for {{ $lifespanLabel }}. Whoever has the phrase can claim it.</p>

                <div class="mt-4 flex items-center gap-3">
                    <button
                        type="button"
                        @click="copyPhrase()"
                        class="inline-flex items-center gap-2 rounded-brand border border-steel-300 bg-surface px-3 py-1.5 text-sm text-ink transition hover:border-signal-300 hover:text-signal-600"
                    >
                        <span x-text="copied ? 'Copied' : 'Copy phrase'"></span>
                    </button>
                    <button
                        type="button"
                        @click="reset()"
                        class="text-sm text-ink-muted underline decoration-steel-300 underline-offset-4 transition hover:text-signal-600"
                    >Send another</button>
                </div>
            </div>

            {{-- rejection state --}}
            <div
                x-show="state === 'error'"
                x-cloak
                x-transition:enter="animate-shake"
                class="mt-4"
            >
                <x-brand::alert variant="error">
                    <span class="font-mono text-[11px] uppercase tracking-[0.2em]">Rejected</span>
                    <p class="mt-1" x-text="errorMessage"></p>
                </x-brand::alert>
            </div>
        </div>

        {{-- DOCK B · RECEIVE ---------------------------------------------- --}}
        <div class="relative rounded-tag border border-steel-200 bg-surface p-6 shadow-sm">
            <span class="absolute left-6 top-0 h-3 w-3 -translate-y-1/2 rounded-full border-2 border-brass-500 bg-surface"></span>

            <div class="mb-5 flex items-baseline justify-between border-b border-dashed border-steel-200 pb-4">
                <div>
                    <span class="font-mono text-[11px] uppercase tracking-[0.2em] text-ink-muted">Dock B</span>
                    <h2 class="font-display font-semibold text-ink">Receive a file</h2>
                </div>
            </div>

            @if (session('drop_share_error'))
                <div class="mb-4 animate-shake">
                    <x-brand::alert variant="error">{{ session('drop_share_error') }}</x-brand::alert>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('drop-share.download') }}"
                x-data="{ submitting: false }"
                @submit="submitting = true"
                class="space-y-4"
            >
                @csrf
                <div class="relative rounded-brand bg-surface-muted px-4 py-3 ticket-notch">
                    <label for="phrase" class="mb-1 block font-mono text-[11px] uppercase tracking-[0.2em] text-ink-muted">Claim phrase</label>
                    <input
                        id="phrase"
                        name="phrase"
                        required
                        placeholder="correct-horse-battery-staple"
                        class="w-full bg-transparent font-mono text-ink placeholder:text-ink-muted/60 focus:outline-none"
                    >
                </div>
                @error('phrase')
                    <p class="font-mono text-xs text-signal-600">{{ $message }}</p>
                @enderror

                <button
                    type="submit"
                    :disabled="submitting"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-brand border border-steel-300 bg-surface px-4 py-2 text-sm font-medium text-ink transition hover:border-signal-300 hover:text-signal-600 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span x-show="!submitting">Receive it</span>
                    <span x-show="submitting">Fetching&hellip;</span>
                </button>
            </form>
        </div>
    </div>

    @push('styles')
        <style>
            [x-cloak] { display: none !important; }

            /* Ticket stub — two half-circle notches bitten out of the edges,
               echoing the punch-hole tag already used on tool cards. */
            .ticket-notch::before,
            .ticket-notch::after {
                content: '';
                position: absolute;
                top: 50%;
                width: 14px;
                height: 14px;
                background: var(--color-surface);
                border-radius: 9999px;
                transform: translateY(-50%);
            }
            .ticket-notch::before { left: -7px; }
            .ticket-notch::after { right: -7px; }

            .gauge-fill {
                background-image: repeating-linear-gradient(
                    45deg,
                    var(--color-signal-500) 0 10px,
                    var(--color-signal-300) 10px 20px
                );
                background-size: 28px 28px;
                animation: gauge-stripes 0.8s linear infinite;
                transition: width 150ms ease-out;
            }

            @keyframes gauge-stripes {
                to { background-position: 28px 0; }
            }

            .animate-stamp-in {
                animation: stamp-in 420ms cubic-bezier(.2, 1.6, .4, 1) both;
            }
            @keyframes stamp-in {
                0% { opacity: 0; transform: scale(1.25) rotate(-6deg); }
                60% { opacity: 1; transform: scale(0.97) rotate(1.5deg); }
                100% { opacity: 1; transform: scale(1) rotate(0deg); }
            }

            .animate-shake {
                animation: shake 380ms ease-in-out both;
            }
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                20% { transform: translateX(-6px); }
                40% { transform: translateX(5px); }
                60% { transform: translateX(-3px); }
                80% { transform: translateX(2px); }
            }

            @media (prefers-reduced-motion: reduce) {
                .gauge-fill { animation: none; }
                .animate-stamp-in, .animate-shake {
                    animation-duration: 1ms;
                    animation-iteration-count: 1;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            function dropShareUpload({ action, maxLabel }) {
                return {
                    state: 'idle', // idle | uploading | success | error
                    dragging: false,
                    file: null,
                    progress: 0,
                    phrase: '',
                    errorMessage: '',
                    copied: false,

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

                    submitFile() {
                        if (!this.file || this.state === 'uploading') return;

                        this.state = 'uploading';
                        this.progress = 0;

                        const formData = new FormData();
                        formData.append('file', this.file);
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', action);
                        xhr.setRequestHeader('Accept', 'application/json');
                        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                        xhr.upload.addEventListener('progress', (e) => {
                            if (e.lengthComputable) {
                                this.progress = Math.round((e.loaded / e.total) * 100);
                            }
                        });

                        xhr.addEventListener('load', () => {
                            let body = {};
                            try { body = JSON.parse(xhr.responseText); } catch (e) {}

                            if (xhr.status >= 200 && xhr.status < 300) {
                                this.phrase = body.phrase;
                                this.state = 'success';
                                return;
                            }

                            this.errorMessage = body.errors?.file?.[0]
                                ?? body.message
                                ?? `That file couldn't be sent. Keep it under ${maxLabel}.`;
                            this.state = 'error';
                        });

                        xhr.addEventListener('error', () => {
                            this.errorMessage = 'The connection dropped mid-send. Try again.';
                            this.state = 'error';
                        });

                        xhr.send(formData);
                    },

                    formatBytes(bytes) {
                        if (bytes < 1024) return `${bytes} B`;
                        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
                        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
                    },

                    copyPhrase() {
                        navigator.clipboard.writeText(this.phrase).then(() => {
                            this.copied = true;
                            setTimeout(() => { this.copied = false; }, 1600);
                        });
                    },

                    reset() {
                        this.state = 'idle';
                        this.file = null;
                        this.progress = 0;
                        this.phrase = '';
                        this.errorMessage = '';
                        this.$refs.fileInput.value = '';
                    },
                };
            }
        </script>
    @endpush
</x-brand::layout>
