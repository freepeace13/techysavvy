<x-brand::layout title="Photo Tweaker">
    <x-brand::page-header eyebrow="Image workshop" title="Photo Tweaker">
        Upload an image, crop it, rotate or flip it, resize it, then export the result
        as PNG, JPEG, or WebP. Everything happens in your browser &mdash; nothing is uploaded anywhere.
    </x-brand::page-header>

    <div x-data="photoTweaker()" x-init="init()">
        <x-brand::panel eyebrow="Step 1" title="Upload an image" x-show="!image" x-cloak>
            <x-brand::dropzone name="file" :required="false">
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
            </x-brand::dropzone>

            <div x-show="errorMessage" x-cloak class="mt-4">
                <x-brand::alert variant="error" x-text="errorMessage"></x-brand::alert>
            </div>
        </x-brand::panel>

        <div x-show="image" x-cloak class="grid grid-cols-1 gap-6 md:grid-cols-3 md:items-start">
            {{-- Canvas + crop overlay -------------------------------------- --}}
            <x-brand::panel eyebrow="Preview" class="md:col-span-2">
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
                        <x-brand::button type="button" variant="secondary" @click="cropping = true; cropBox = null">
                            Crop
                        </x-brand::button>
                    </template>
                    <template x-if="cropping">
                        <x-brand::button type="button" @click="applyCrop()" ::disabled="!cropBox">
                            Apply crop
                        </x-brand::button>
                    </template>
                    <template x-if="cropping">
                        <x-brand::button type="button" variant="secondary" @click="cropping = false; cropBox = null">
                            Cancel crop
                        </x-brand::button>
                    </template>

                    <span class="mx-1 h-6 w-px bg-steel-200"></span>

                    <x-brand::button type="button" variant="secondary" @click="rotate(-90)" title="Rotate left">&#8634; Rotate</x-brand::button>
                    <x-brand::button type="button" variant="secondary" @click="rotate(90)" title="Rotate right">&#8635; Rotate</x-brand::button>
                    <x-brand::button type="button" variant="secondary" @click="flip('horizontal')">&#8596; Flip</x-brand::button>
                    <x-brand::button type="button" variant="secondary" @click="flip('vertical')">&#8597; Flip</x-brand::button>

                    <span class="mx-1 h-6 w-px bg-steel-200"></span>

                    <button type="button" @click="reset()" class="text-sm text-ink-muted underline decoration-steel-300 underline-offset-4 transition hover:text-signal-600">
                        Start over
                    </button>
                </div>
            </x-brand::panel>

            {{-- Resize + export ------------------------------------------- --}}
            <div class="flex flex-col gap-6">
                <x-brand::panel eyebrow="Step 2" title="Resize">
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

                    <x-brand::button type="button" variant="secondary" class="mt-4 w-full" @click="applyResize()">
                        Apply resize
                    </x-brand::button>
                </x-brand::panel>

                <x-brand::panel eyebrow="Step 3" title="Export">
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

                    <x-brand::button type="button" class="mt-4 w-full" @click="download()">
                        Download
                    </x-brand::button>
                </x-brand::panel>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            [x-cloak] { display: none !important; }
        </style>
    @endpush

    @push('scripts')
        <script>
            function photoTweaker() {
                return {
                    dragging: false,
                    file: null,
                    image: null,
                    errorMessage: '',
                    cropping: false,
                    cropBox: null,
                    cropDragStart: null,
                    resizeWidth: 0,
                    resizeHeight: 0,
                    lockAspect: true,
                    exportFormat: 'image/png',
                    exportQuality: 0.92,

                    get dimensionsLabel() {
                        if (!this.$refs.canvas) return '';
                        return `${this.$refs.canvas.width} × ${this.$refs.canvas.height}`;
                    },

                    init() {
                        // nothing to preload; canvas is created on demand once an image is chosen.
                    },

                    handleSelect(event) {
                        this.setFile(event.target.files[0] ?? null);
                    },

                    handleDrop(event) {
                        this.dragging = false;
                        this.setFile(event.dataTransfer.files[0] ?? null);
                    },

                    setFile(file) {
                        this.errorMessage = '';

                        if (!file) return;

                        if (!file.type.startsWith('image/')) {
                            this.errorMessage = 'That file is not an image. Choose a PNG, JPEG, WebP, or similar image file.';
                            return;
                        }

                        this.file = file;

                        const img = new Image();
                        const url = URL.createObjectURL(file);

                        img.onload = () => {
                            this.image = img;
                            this.$nextTick(() => this.drawImageToCanvas(img));
                            URL.revokeObjectURL(url);
                        };

                        img.onerror = () => {
                            this.errorMessage = 'That image could not be read. Try a different file.';
                            URL.revokeObjectURL(url);
                        };

                        img.src = url;
                    },

                    drawImageToCanvas(source) {
                        const canvas = this.$refs.canvas;
                        canvas.width = source.width ?? source.naturalWidth;
                        canvas.height = source.height ?? source.naturalHeight;
                        canvas.getContext('2d').drawImage(source, 0, 0);
                        this.syncResizeFieldsFromCanvas();
                    },

                    syncResizeFieldsFromCanvas() {
                        this.resizeWidth = this.$refs.canvas.width;
                        this.resizeHeight = this.$refs.canvas.height;
                    },

                    rotate(degrees) {
                        const canvas = this.$refs.canvas;
                        const rotated = document.createElement('canvas');
                        rotated.width = canvas.height;
                        rotated.height = canvas.width;

                        const ctx = rotated.getContext('2d');
                        ctx.translate(rotated.width / 2, rotated.height / 2);
                        ctx.rotate((degrees * Math.PI) / 180);
                        ctx.drawImage(canvas, -canvas.width / 2, -canvas.height / 2);

                        this.replaceCanvas(rotated);
                    },

                    flip(axis) {
                        const canvas = this.$refs.canvas;
                        const flipped = document.createElement('canvas');
                        flipped.width = canvas.width;
                        flipped.height = canvas.height;

                        const ctx = flipped.getContext('2d');
                        if (axis === 'horizontal') {
                            ctx.translate(flipped.width, 0);
                            ctx.scale(-1, 1);
                        } else {
                            ctx.translate(0, flipped.height);
                            ctx.scale(1, -1);
                        }
                        ctx.drawImage(canvas, 0, 0);

                        this.replaceCanvas(flipped);
                    },

                    onWidthInput() {
                        if (!this.lockAspect || !this.$refs.canvas.height) return;
                        const ratio = this.$refs.canvas.height / this.$refs.canvas.width;
                        this.resizeHeight = Math.max(1, Math.round(this.resizeWidth * ratio));
                    },

                    onHeightInput() {
                        if (!this.lockAspect || !this.$refs.canvas.width) return;
                        const ratio = this.$refs.canvas.width / this.$refs.canvas.height;
                        this.resizeWidth = Math.max(1, Math.round(this.resizeHeight * ratio));
                    },

                    applyResize() {
                        const width = Math.max(1, Math.round(this.resizeWidth));
                        const height = Math.max(1, Math.round(this.resizeHeight));
                        const canvas = this.$refs.canvas;

                        const resized = document.createElement('canvas');
                        resized.width = width;
                        resized.height = height;
                        resized.getContext('2d').drawImage(canvas, 0, 0, canvas.width, canvas.height, 0, 0, width, height);

                        this.replaceCanvas(resized);
                    },

                    canvasPoint(event) {
                        const canvas = this.$refs.canvas;
                        const rect = canvas.getBoundingClientRect();
                        const scaleX = canvas.width / rect.width;
                        const scaleY = canvas.height / rect.height;

                        return {
                            x: Math.min(Math.max((event.clientX - rect.left) * scaleX, 0), canvas.width),
                            y: Math.min(Math.max((event.clientY - rect.top) * scaleY, 0), canvas.height),
                        };
                    },

                    cropStart(event) {
                        this.cropDragStart = this.canvasPoint(event);
                        this.cropBox = { x: this.cropDragStart.x, y: this.cropDragStart.y, width: 0, height: 0 };
                    },

                    cropMove(event) {
                        if (!this.cropDragStart) return;
                        const point = this.canvasPoint(event);

                        this.cropBox = {
                            x: Math.min(this.cropDragStart.x, point.x),
                            y: Math.min(this.cropDragStart.y, point.y),
                            width: Math.abs(point.x - this.cropDragStart.x),
                            height: Math.abs(point.y - this.cropDragStart.y),
                        };
                    },

                    cropEnd() {
                        this.cropDragStart = null;
                    },

                    cropBoxStyle() {
                        if (!this.cropBox) return '';

                        const canvas = this.$refs.canvas;
                        const rect = canvas.getBoundingClientRect();
                        const scaleX = rect.width / canvas.width;
                        const scaleY = rect.height / canvas.height;
                        const stageRect = this.$refs.stage.getBoundingClientRect();
                        const offsetX = rect.left - stageRect.left;
                        const offsetY = rect.top - stageRect.top;

                        return `left: ${offsetX + this.cropBox.x * scaleX}px; top: ${offsetY + this.cropBox.y * scaleY}px; `
                            + `width: ${this.cropBox.width * scaleX}px; height: ${this.cropBox.height * scaleY}px;`;
                    },

                    applyCrop() {
                        if (!this.cropBox || this.cropBox.width < 1 || this.cropBox.height < 1) return;

                        const canvas = this.$refs.canvas;
                        const width = Math.round(this.cropBox.width);
                        const height = Math.round(this.cropBox.height);

                        const cropped = document.createElement('canvas');
                        cropped.width = width;
                        cropped.height = height;
                        cropped.getContext('2d').drawImage(
                            canvas,
                            Math.round(this.cropBox.x), Math.round(this.cropBox.y), width, height,
                            0, 0, width, height
                        );

                        this.replaceCanvas(cropped);
                        this.cropping = false;
                        this.cropBox = null;
                    },

                    replaceCanvas(source) {
                        const canvas = this.$refs.canvas;
                        canvas.width = source.width;
                        canvas.height = source.height;
                        canvas.getContext('2d').drawImage(source, 0, 0);
                        this.syncResizeFieldsFromCanvas();
                    },

                    download() {
                        const canvas = this.$refs.canvas;
                        const extension = this.exportFormat.split('/')[1].replace('jpeg', 'jpg');
                        const quality = this.exportFormat === 'image/png' ? undefined : this.exportQuality;

                        canvas.toBlob((blob) => {
                            if (!blob) {
                                this.errorMessage = 'The image could not be exported. Try a different format.';
                                return;
                            }

                            const link = document.createElement('a');
                            link.href = URL.createObjectURL(blob);
                            link.download = `photo-tweaker.${extension}`;
                            link.click();
                            URL.revokeObjectURL(link.href);
                        }, this.exportFormat, quality);
                    },

                    reset() {
                        this.file = null;
                        this.image = null;
                        this.errorMessage = '';
                        this.cropping = false;
                        this.cropBox = null;
                        if (this.$refs.fileInput) this.$refs.fileInput.value = '';
                    },
                };
            }
        </script>
    @endpush
</x-brand::layout>
