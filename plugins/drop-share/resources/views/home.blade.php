@php
    $maxKb = (int) config('drop-share.max_upload_kb');
    $maxMb = $maxKb >= 1024 ? rtrim(rtrim(number_format($maxKb / 1024, 1), '0'), '.') : null;
    $maxLabel = $maxMb ? "{$maxMb} MB" : "{$maxKb} KB";

    $lifespanHours = (int) config('drop-share.lifespan_hours');
    $lifespanLabel = $lifespanHours % 24 === 0
        ? \Illuminate\Support\Str::plural('day', $lifespanHours / 24, true)
        : \Illuminate\Support\Str::plural('hour', $lifespanHours, true);
@endphp

@pluginAssets('drop-share')

<x-ui::layout title="Drop Share">
    <x-ui::page-header eyebrow="Package drop &middot; send &amp; receive" title="Drop Share">
        Send a file, get a claim phrase. Trade the phrase back in and it's yours &mdash;
        up to {{ $maxLabel }}, held for {{ $lifespanLabel }} before it's swept off the dock.
    </x-ui::page-header>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 md:items-start">
        {{-- DOCK A · SEND ------------------------------------------------ --}}
        <div
            x-data="dropShareUpload({
                action: '{{ route('drop-share.upload') }}',
                maxLabel: '{{ $maxLabel }}',
            })"
        >
            <x-ui::panel eyebrow="Dock A" title="Send a file" :meta="$maxLabel . ' max'">
                <form
                    method="POST"
                    action="{{ route('drop-share.upload') }}"
                    enctype="multipart/form-data"
                    @submit.prevent="submitFile()"
                >
                    @csrf

                    <x-ui::dropzone name="file" x-show="state !== 'success'">
                        <svg viewBox="0 0 48 48" fill="none" class="h-10 w-10 text-steel-300" aria-hidden="true">
                            <path d="M6 16 24 7l18 9-18 9-18-9Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M6 16v16l18 9 18-9V16" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M24 25v16" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <p class="text-sm font-medium text-ink">Drop a file here</p>
                        <p class="font-mono text-xs text-ink-muted">or click to browse</p>

                        <x-slot:selected>
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
                        </x-slot:selected>
                    </x-ui::dropzone>

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

                    <x-ui::button
                        x-show="state !== 'success'"
                        type="submit"
                        class="mt-5 w-full"
                        ::disabled="!file || state === 'uploading'"
                    >
                        <span x-show="state !== 'uploading'">Send it</span>
                        <span x-show="state === 'uploading'">Sending&hellip;</span>
                    </x-ui::button>
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
                        <x-ui::button type="button" variant="secondary" @click="copyPhrase()">
                            <span x-text="copied ? 'Copied' : 'Copy phrase'"></span>
                        </x-ui::button>
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
                    <x-ui::alert variant="error">
                        <span class="font-mono text-[11px] uppercase tracking-[0.2em]">Rejected</span>
                        <p class="mt-1" x-text="errorMessage"></p>
                    </x-ui::alert>
                </div>
            </x-ui::panel>
        </div>

        {{-- DOCK B · RECEIVE ---------------------------------------------- --}}
        <x-ui::panel eyebrow="Dock B" title="Receive a file">
            @if (session('drop_share_error'))
                <div class="mb-4 animate-shake">
                    <x-ui::alert variant="error">{{ session('drop_share_error') }}</x-ui::alert>
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

                <x-ui::button type="submit" variant="secondary" class="w-full" ::disabled="submitting">
                    <span x-show="!submitting">Receive it</span>
                    <span x-show="submitting">Fetching&hellip;</span>
                </x-ui::button>
            </form>
        </x-ui::panel>
    </div>
</x-ui::layout>
