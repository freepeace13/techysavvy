import { createQr, drawToCanvas, QrTooLongError, toSvg } from './qr.js';

function readAsDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

function loadImage(src) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = reject;
        image.src = src;
    });
}

function save(blob, filename) {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
    URL.revokeObjectURL(url);
}

// Alpine component for the page, exposed as window.qrForge by qr-forge.js.
export function qrForge() {
    return {
        text: '',
        pngSize: 512,
        errorMessage: '',
        svg: '',
        level: 'M',
        qr: null,

        // Logo state. `file` and `dragging` are the names <x-ui::dropzone> binds to.
        file: null,
        dragging: false,
        logo: null,
        logoDataUrl: null,

        get canDownload() {
            return this.qr !== null;
        },

        init() {
            this.$watch('text', () => this.render());
        },

        render() {
            this.errorMessage = '';
            this.qr = null;
            this.svg = '';

            if (!this.text.trim()) return;

            try {
                this.qr = createQr(this.text, { withLogo: this.logo !== null });
            } catch (error) {
                if (!(error instanceof QrTooLongError)) throw error;
                this.errorMessage = error.message;
                return;
            }

            this.level = this.qr.level;
            this.svg = toSvg(this.qr, { logoDataUrl: this.logoDataUrl });
        },

        handleSelect(event) {
            const file = event.target.files[0] ?? null;
            event.target.value = '';

            return this.setLogo(file);
        },

        handleDrop(event) {
            this.dragging = false;

            return this.setLogo(event.dataTransfer.files[0] ?? null);
        },

        async setLogo(file) {
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                this.errorMessage = 'That file is not an image. Choose a PNG, JPEG, SVG, or similar image file.';
                return;
            }

            try {
                const dataUrl = await readAsDataUrl(file);
                this.logo = await loadImage(dataUrl);
                this.logoDataUrl = dataUrl;
                this.file = file;
            } catch {
                this.removeLogo();
                this.errorMessage = 'That image could not be read. Try a different file.';
                return;
            }

            this.render();
        },

        removeLogo() {
            this.file = null;
            this.logo = null;
            this.logoDataUrl = null;
            this.level = 'M';
            this.render();
        },

        downloadSvg() {
            if (!this.canDownload) return;

            save(new Blob([this.svg], { type: 'image/svg+xml' }), 'qr-forge.svg');
        },

        downloadPng() {
            if (!this.canDownload) return;

            const size = Number(this.pngSize);
            const canvas = document.createElement('canvas');
            canvas.width = canvas.height = size;
            drawToCanvas(canvas.getContext('2d'), this.qr, size, this.logo);
            canvas.toBlob((blob) => save(blob, 'qr-forge.png'), 'image/png');
        },
    };
}
