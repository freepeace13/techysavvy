import { bodyView, expiresIn, formatBytes, headerRows, mergeRequests, queryRows, relativeTime, truncationNotice } from './format.js';

// Alpine component for the viewer page, exposed as window.webhookInspector by
// webhook-inspector.js. `options` is the root element's dataset (strings).
export function webhookInspector(options = {}) {
    return {
        pollUrl: options.pollUrl,
        captureUrl: options.captureUrl,
        expiresAt: options.expiresAt,
        intervalMs: Number(options.intervalMs) || 2000,
        maxRequests: Number(options.maxRequests) || 200,
        maxBodyKb: Number(options.maxBodyKb) || 256,
        expired: options.expired === 'true',
        requests: [],
        selectedId: null,
        lastId: 0,
        timer: null,
        now: Date.now(),
        tab: 'body',
        copiedLabel: null,

        init() {
            if (this.expired) return;
            document.addEventListener('visibilitychange', () => {
                document.visibilityState === 'hidden' ? this.stop() : this.start();
            });
            this.start();
        },

        start() {
            if (this.timer || this.expired) return;
            this.poll();
            this.timer = setInterval(() => this.poll(), this.intervalMs);
        },

        stop() {
            clearInterval(this.timer);
            this.timer = null;
        },

        async poll() {
            let response;
            try {
                response = await fetch(`${this.pollUrl}?after=${this.lastId}`, { headers: { Accept: 'application/json' } });
            } catch {
                return; // Offline or server hiccup: try again on the next tick.
            }
            this.now = Date.now();
            if (response.status === 404 || response.status === 410) {
                this.expired = true;
                this.stop();
                return;
            }
            if (!response.ok) return;

            const data = await response.json();
            this.expiresAt = data.expires_at;
            this.requests = mergeRequests(this.requests, data.requests, this.maxRequests);
            if (this.requests.length) {
                this.lastId = Math.max(this.lastId, this.requests[0].id);
                if (this.selectedId === null) this.selectedId = this.requests[0].id;
            }
        },

        select(id) {
            this.selectedId = id;
        },

        get selected() {
            return this.requests.find((req) => req.id === this.selectedId) ?? null;
        },

        get selectedBody() {
            return this.selected ? bodyView(this.selected) : { kind: 'empty', text: '', copyable: false };
        },

        get selectedHeaders() {
            return this.selected ? headerRows(this.selected.headers) : [];
        },

        get selectedQuery() {
            return this.selected ? queryRows(this.selected.query) : [];
        },

        get selectedNotice() {
            return this.selected ? truncationNotice(this.selected, this.maxBodyKb) : null;
        },

        get expiryText() {
            return this.expired ? 'Expired' : expiresIn(this.expiresAt, this.now);
        },

        rowTime(req) {
            return relativeTime(req.received_at, this.now);
        },

        rowSize(req) {
            return formatBytes(req.body_size);
        },

        async copy(text, label) {
            await navigator.clipboard.writeText(text);
            this.copiedLabel = label;
            setTimeout(() => {
                if (this.copiedLabel === label) this.copiedLabel = null;
            }, 1500);
        },

        copyBody() {
            if (!this.selected || !bodyView(this.selected).copyable) return;
            return this.copy(this.selected.body, 'body');
        },

        copyCaptureUrl() {
            return this.copy(this.captureUrl, 'url');
        },
    };
}
