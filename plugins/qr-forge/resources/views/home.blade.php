@pluginAssets('qr-forge')

@php
    $fieldClass = 'rounded-brand border border-steel-300 bg-surface px-3 py-2 text-ink placeholder:text-ink-muted focus:border-signal-500 focus:outline-none focus:ring-2 focus:ring-signal-100';
@endphp

<x-ui::layout title="QR Forge">
    <x-ui::page-header eyebrow="Code workshop" title="QR Forge">
        Turn any text or URL into a QR code, optionally with your logo in the middle, and download it
        as PNG or SVG. Everything happens in your browser &mdash; nothing is uploaded anywhere.
    </x-ui::page-header>

    <div x-data="qrForge()" x-init="init()" class="grid grid-cols-1 gap-6 md:grid-cols-2 md:items-start">
        <div class="flex flex-col gap-6">
            <x-ui::panel eyebrow="Step 1" title="What should it say?">
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium text-ink">Text or URL</span>
                    <textarea
                        x-model.debounce.150ms="text"
                        rows="4"
                        placeholder="https://example.com"
                        autofocus
                        class="{{ $fieldClass }} font-mono text-sm"
                    ></textarea>
                </label>
            </x-ui::panel>

            <x-ui::panel eyebrow="Step 2" title="Add a logo" meta="optional">
                <x-ui::dropzone name="logo" :required="false" accept="image/*" idle-expr="true">
                    <p class="text-sm font-medium text-ink">Drop a logo here</p>
                    <p class="font-mono text-xs text-ink-muted">or click to browse</p>

                    <x-slot:selected>
                        <span class="truncate" x-text="file?.name"></span>
                        <x-ui::button type="button" variant="ghost" @click="removeLogo()">Remove</x-ui::button>
                    </x-slot:selected>
                </x-ui::dropzone>

                <p class="mt-3 text-xs text-ink-muted">
                    With a logo, the code uses the highest error correction so it still scans.
                </p>
            </x-ui::panel>
        </div>

        <x-ui::panel eyebrow="Step 3" title="Preview & download">
            <div x-show="errorMessage" x-cloak class="mb-4">
                <x-ui::alert variant="error" x-text="errorMessage"></x-ui::alert>
            </div>

            <div class="mx-auto flex aspect-square w-full max-w-xs items-center justify-center rounded-brand bg-surface-sunken">
                <div x-show="svg" x-cloak x-html="svg" class="w-full [&>svg]:block [&>svg]:h-auto [&>svg]:w-full"></div>
                <p x-show="!svg" class="px-6 text-center font-mono text-xs text-ink-muted">
                    Your QR code will appear here.
                </p>
            </div>

            <label class="mt-5 flex flex-col gap-1.5">
                <span class="text-sm font-medium text-ink">PNG size</span>
                <select x-model.number="pngSize" class="{{ $fieldClass }}">
                    <option value="256">256 &times; 256 px</option>
                    <option value="512">512 &times; 512 px</option>
                    <option value="1024">1024 &times; 1024 px</option>
                    <option value="2048">2048 &times; 2048 px</option>
                </select>
            </label>

            <div class="mt-4 grid grid-cols-2 gap-3">
                <x-ui::button type="button" @click="downloadPng()" ::disabled="!canDownload">Download PNG</x-ui::button>
                <x-ui::button type="button" variant="secondary" @click="downloadSvg()" ::disabled="!canDownload">Download SVG</x-ui::button>
            </div>
        </x-ui::panel>
    </div>
</x-ui::layout>
