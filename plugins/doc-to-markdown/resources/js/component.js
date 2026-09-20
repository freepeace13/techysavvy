import { messageForFailure, validateFile } from './upload.js';

const TIMEOUT_MS = 120_000;

function escapeHtml(text) {
    return text.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
}

// Alpine component behind the Doc to Markdown page.
export function docToMarkdown({ action, maxBytes }) {
    return {
        state: 'idle', // idle | uploading | success | error
        dragging: false,
        file: null,
        progress: 0,
        converting: false, // upload finished, server still working
        markdown: '',
        markdownHtml: '',
        downloadName: 'converted.md',
        errorMessage: '',
        status: '', // screen-reader announcements
        view: 'markdown', // markdown | raw
        copied: false,
        copyFailed: false,
        xhr: null,

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

            const problem = validateFile(file, { maxBytes });
            if (problem) {
                this.fail(problem);
                return;
            }

            this.$refs.fileInput.files = this.toFileList(file);
            this.uploadFile();
        },

        toFileList(file) {
            const dt = new DataTransfer();
            dt.items.add(file);
            return dt.files;
        },

        fail(message) {
            this.errorMessage = message;
            this.status = message;
            this.state = 'error';
            this.converting = false;
            this.xhr = null;
            // Clear the input so picking the same file again fires `change`.
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },

        uploadFile() {
            if (!this.file) return;

            this.state = 'uploading';
            this.progress = 0;
            this.converting = false;
            this.status = 'Uploading file';

            const formData = new FormData();
            formData.append('file', this.file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');

            const xhr = new XMLHttpRequest();
            this.xhr = xhr;
            xhr.open('POST', action);
            xhr.responseType = 'json';
            xhr.timeout = TIMEOUT_MS;
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable) {
                    this.progress = Math.round((event.loaded / event.total) * 100);
                }
            });

            // The bytes are all sent; whatever time remains is conversion.
            xhr.upload.addEventListener('load', () => {
                this.progress = 100;
                this.converting = true;
                this.status = 'Converting file';
            });

            xhr.addEventListener('load', () => {
                if (this.xhr !== xhr) return; // cancelled

                const body = xhr.response ?? {};

                if (xhr.status < 200 || xhr.status >= 300) {
                    this.fail(messageForFailure(xhr.status, body));
                    return;
                }

                this.showResult(body);
            });

            xhr.addEventListener('error', () => {
                if (this.xhr === xhr) this.fail('The upload failed — check your connection and try again.');
            });

            xhr.addEventListener('timeout', () => {
                if (this.xhr === xhr) this.fail('The conversion took too long. Try a smaller file.');
            });

            xhr.send(formData);
        },

        showResult(body) {
            this.markdown = body.markdown ?? '';

            try {
                this.markdownHtml = window.docToMarkdownRender(this.markdown);
            } catch (e) {
                // A preview failure must not lose the conversion: show it as plain text.
                this.markdownHtml = `<pre>${escapeHtml(this.markdown)}</pre>`;
            }

            this.downloadName = body.filename ?? 'converted.md';
            this.converting = false;
            this.xhr = null;
            this.state = 'success';
            this.status = 'Conversion complete';
        },

        cancel() {
            const xhr = this.xhr;
            this.xhr = null; // handlers ignore the aborted request
            xhr?.abort();
            this.reset();
            this.status = 'Upload cancelled';
        },

        download() {
            const blob = new Blob([this.markdown], { type: 'text/markdown' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = this.downloadName;
            // Attached to the DOM for Firefox; revoked late so Safari has
            // started the download before the URL disappears.
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        },

        async copy() {
            this.copyFailed = false;

            try {
                await this.writeClipboard();
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            } catch (e) {
                this.copyFailed = true;
                setTimeout(() => { this.copyFailed = false; }, 3000);
            }
        },

        // Write both text/html and text/plain so pasting into a rich-text
        // target (email, docs, chat) renders actual headings/bullets/bold
        // instead of literal markdown syntax; plain-text targets still get
        // the markdown source. Falls back step by step where the clipboard
        // API is missing (e.g. non-HTTPS pages) or refuses.
        async writeClipboard() {
            if (navigator.clipboard && window.ClipboardItem) {
                try {
                    await navigator.clipboard.write([new ClipboardItem({
                        'text/html': new Blob([this.markdownHtml], { type: 'text/html' }),
                        'text/plain': new Blob([this.markdown], { type: 'text/plain' }),
                    })]);
                    return;
                } catch (e) {
                    // fall through to plain text
                }
            }

            if (navigator.clipboard?.writeText) {
                try {
                    await navigator.clipboard.writeText(this.markdown);
                    return;
                } catch (e) {
                    // fall through to the legacy path
                }
            }

            const area = document.createElement('textarea');
            area.value = this.markdown;
            area.setAttribute('readonly', '');
            area.style.position = 'fixed';
            area.style.opacity = '0';
            document.body.appendChild(area);
            area.select();

            try {
                if (!document.execCommand('copy')) throw new Error('copy command refused');
            } finally {
                area.remove();
            }
        },

        reset() {
            this.state = 'idle';
            this.file = null;
            this.progress = 0;
            this.converting = false;
            this.markdown = '';
            this.markdownHtml = '';
            this.errorMessage = '';
            this.status = '';
            this.view = 'markdown';
            this.copied = false;
            this.copyFailed = false;
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },
    };
}
