@pluginAssets('photo-tweaker')

<x-ui::layout title="Photo Tweaker">
    <x-ui::page-header eyebrow="Image workshop" title="Photo Tweaker">
        Upload an image, crop it, rotate or flip it, resize it, then export the result
        as PNG, JPEG, or WebP. Everything happens in your browser &mdash; nothing is uploaded anywhere.
    </x-ui::page-header>

    <div x-data="photoTweaker()" x-init="init()">
        <x-ui::panel eyebrow="Step 1" title="Upload an image" x-show="!image" x-cloak>
            <x-ui::dropzone name="file" :required="false" accept="image/*" idle-expr="true">
                <svg viewBox="0 0 48 48" fill="none" class="h-10 w-10 text-steel-300" aria-hidden="true">
                    <path d="M6 16 24 7l18 9-18 9-18-9Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M6 16v16l18 9 18-9V16" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M24 25v16" stroke="currentColor" stroke-width="2"/>
                </svg>
                <p class="text-sm font-medium text-ink">Drop an image here</p>
                <p class="font-mono text-xs text-ink-muted">or click to browse</p>

                <x-slot:selected>
                    <span class="truncate" x-text="file?.name"></span>
                </x-slot:selected>
            </x-ui::dropzone>
        </x-ui::panel>

        <div x-show="errorMessage" x-cloak class="mb-6">
            <x-ui::alert variant="error" x-text="errorMessage"></x-ui::alert>
        </div>

        <div x-show="image" x-cloak class="grid grid-cols-1 gap-6 md:grid-cols-3 md:items-start">
            {{-- Canvas + crop overlay -------------------------------------- --}}
            <x-ui::panel eyebrow="Preview" class="md:col-span-2">
                <p class="mb-3 font-mono text-xs text-ink-muted" x-text="dimensionsLabel"></p>

                <div
                    class="relative mx-auto flex max-h-[28rem] w-fit items-center justify-center overflow-hidden rounded-brand bg-surface-sunken"
                    x-ref="stage"
                >
                    <canvas x-ref="canvas" class="block max-h-[28rem] max-w-full select-none"></canvas>

                    <div
                        x-show="cropping"
                        x-cloak
                        class="absolute inset-0 cursor-crosshair touch-none"
                        @pointerdown="cropStart($event)"
                        @pointermove="cropMove($event)"
                        @pointerup="cropEnd($event)"
                        @pointerleave="cropEnd($event)"
                    >
                        <div
                            x-show="cropBox"
                            class="absolute border-2 border-signal-500 bg-signal-500/10"
                            :style="cropBoxStyle()"
                        ></div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <template x-if="!cropping">
                        <x-ui::button type="button" variant="secondary" @click="cropping = true; cropBox = null">
                            Crop
                        </x-ui::button>
                    </template>
                    <template x-if="cropping">
                        <x-ui::button type="button" @click="applyCrop()" ::disabled="!cropBox">
                            Apply crop
                        </x-ui::button>
                    </template>
                    <template x-if="cropping">
                        <x-ui::button type="button" variant="secondary" @click="cropping = false; cropBox = null">
                            Cancel crop
                        </x-ui::button>
                    </template>

                    <span class="mx-1 h-6 w-px bg-steel-200"></span>

                    <x-ui::button type="button" variant="secondary" @click="rotate(-90)" title="Rotate left">&#8634; Rotate</x-ui::button>
                    <x-ui::button type="button" variant="secondary" @click="rotate(90)" title="Rotate right">&#8635; Rotate</x-ui::button>
                    <x-ui::button type="button" variant="secondary" @click="flip('horizontal')">&#8596; Flip</x-ui::button>
                    <x-ui::button type="button" variant="secondary" @click="flip('vertical')">&#8597; Flip</x-ui::button>

                    <span class="mx-1 h-6 w-px bg-steel-200"></span>

                    <button type="button" @click="reset()" class="text-sm text-ink-muted underline decoration-steel-300 underline-offset-4 transition hover:text-signal-600">
                        Start over
                    </button>
                </div>
            </x-ui::panel>

            {{-- Resize + export ------------------------------------------- --}}
            <div class="flex flex-col gap-6">
                <x-ui::panel eyebrow="Step 2" title="Resize">
                    <div class="flex items-end gap-3">
                        <label class="flex flex-1 flex-col gap-1.5">
                            <span class="text-sm font-medium text-ink">Width</span>
                            <input type="number" min="1" x-model.number="resizeWidth" @input="onWidthInput()" class="rounded-brand border border-steel-300 bg-surface px-3 py-2 text-ink focus:border-signal-500 focus:outline-none focus:ring-2 focus:ring-signal-100">
                        </label>
                        <label class="flex flex-1 flex-col gap-1.5">
                            <span class="text-sm font-medium text-ink">Height</span>
                            <input type="number" min="1" x-model.number="resizeHeight" @input="onHeightInput()" class="rounded-brand border border-steel-300 bg-surface px-3 py-2 text-ink focus:border-signal-500 focus:outline-none focus:ring-2 focus:ring-signal-100">
                        </label>
                    </div>

                    <label class="mt-3 flex items-center gap-2 text-sm text-ink-muted">
                        <input type="checkbox" x-model="lockAspect" class="rounded border-steel-300 text-signal-500 focus:ring-signal-100">
                        Lock aspect ratio
                    </label>

                    <x-ui::button type="button" variant="secondary" class="mt-4 w-full" @click="applyResize()">
                        Apply resize
                    </x-ui::button>
                </x-ui::panel>

                <x-ui::panel eyebrow="Step 3" title="Export">
                    <label class="flex flex-col gap-1.5">
                        <span class="text-sm font-medium text-ink">Format</span>
                        <select x-model="exportFormat" class="rounded-brand border border-steel-300 bg-surface px-3 py-2 text-ink focus:border-signal-500 focus:outline-none focus:ring-2 focus:ring-signal-100">
                            <option value="image/png">PNG</option>
                            <option value="image/jpeg">JPEG</option>
                            <option value="image/webp">WebP</option>
                        </select>
                    </label>

                    <label class="mt-3 flex flex-col gap-1.5" x-show="exportFormat !== 'image/png'">
                        <span class="text-sm font-medium text-ink">
                            Quality &mdash; <span x-text="Math.round(exportQuality * 100)"></span>%
                        </span>
                        <input type="range" min="0.1" max="1" step="0.05" x-model.number="exportQuality" class="accent-signal-500">
                    </label>

                    <x-ui::button type="button" class="mt-4 w-full" @click="download()">
                        Download
                    </x-ui::button>
                </x-ui::panel>
            </div>
        </div>
    </div>
</x-ui::layout>
