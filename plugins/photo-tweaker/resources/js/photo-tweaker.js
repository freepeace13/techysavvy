import '../css/photo-tweaker.css';

// Referenced by the page's x-data="photoTweaker(...)".
window.photoTweaker = function () {
    return {
        dragging: false,
        file: null,
        image: null,
        errorMessage: '',
        cropping: false,
        cropBox: null,
        cropDragStart: null,
        canvasWidth: 0,
        canvasHeight: 0,
        resizeWidth: 0,
        resizeHeight: 0,
        lockAspect: true,
        exportFormat: 'image/png',
        exportQuality: 0.92,

        get dimensionsLabel() {
            return this.canvasWidth ? `${this.canvasWidth} × ${this.canvasHeight}` : '';
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
            this.canvasWidth = this.resizeWidth = this.$refs.canvas.width;
            this.canvasHeight = this.resizeHeight = this.$refs.canvas.height;
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
            let canvas = this.$refs.canvas;
            const extension = this.exportFormat.split('/')[1].replace('jpeg', 'jpg');
            const quality = this.exportFormat === 'image/png' ? undefined : this.exportQuality;

            if (this.exportFormat === 'image/jpeg') {
                // JPEG has no alpha channel; flatten onto white instead of black.
                const flat = document.createElement('canvas');
                flat.width = canvas.width;
                flat.height = canvas.height;
                const ctx = flat.getContext('2d');
                ctx.fillStyle = '#fff';
                ctx.fillRect(0, 0, flat.width, flat.height);
                ctx.drawImage(canvas, 0, 0);
                canvas = flat;
            }

            canvas.toBlob((blob) => {
                if (!blob) {
                    this.errorMessage = 'The image could not be exported. Try a different format.';
                    return;
                }

                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `photo-tweaker.${extension}`;
                link.click();
                setTimeout(() => URL.revokeObjectURL(link.href), 1000);
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
};
