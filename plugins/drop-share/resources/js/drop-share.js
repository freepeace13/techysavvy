import '../css/drop-share.css';

// Referenced by the page's x-data="dropShareUpload(...)".
window.dropShareUpload = function ({ action, maxLabel }) {
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
};
