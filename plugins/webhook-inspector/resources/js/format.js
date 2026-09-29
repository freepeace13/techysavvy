// Pure display helpers for the viewer. Every value they return is plain text:
// the page renders it with x-text, never as HTML.

export function formatBytes(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function parseJson(body, contentType) {
    const looksJson = /json/i.test(contentType ?? '') || /^\s*[{[]/.test(body);
    if (!looksJson) return undefined;
    try {
        return JSON.parse(body);
    } catch {
        return undefined;
    }
}

export function bodyView(req) {
    if (req.is_binary) {
        return { kind: 'binary', text: `binary, ${formatBytes(req.body_size)}`, copyable: false };
    }
    if (!req.body) {
        return { kind: 'empty', text: '(empty body)', copyable: false };
    }
    const parsed = parseJson(req.body, req.content_type);
    if (parsed !== undefined) {
        return { kind: 'json', text: JSON.stringify(parsed, null, 2), copyable: true };
    }
    return { kind: 'text', text: req.body, copyable: true };
}

export function truncationNotice(req, maxKb) {
    if (!req.truncated) return null;
    return `Truncated: showing first ${maxKb} KB of ${Math.round(req.body_size / 1024)} KB`;
}

export function relativeTime(iso, now = Date.now()) {
    const seconds = Math.max(0, Math.floor((now - Date.parse(iso)) / 1000));
    if (seconds < 5) return 'just now';
    if (seconds < 60) return `${seconds}s ago`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    return `${Math.floor(seconds / 3600)}h ago`;
}

export function expiresIn(iso, now = Date.now()) {
    const minutes = Math.floor((Date.parse(iso) - now) / 60000);
    if (minutes <= 0) return 'Expired';
    const hours = Math.floor(minutes / 60);
    return hours > 0 ? `Expires in ${hours}h ${minutes % 60}m` : `Expires in ${minutes}m`;
}

export function queryRows(query) {
    return [...new URLSearchParams(query ?? '')];
}

export function headerRows(headers) {
    return Object.keys(headers ?? {})
        .sort()
        .flatMap((name) => headers[name].map((value) => [name, value]));
}

// Union by id, newest first, capped like the server's per-bin limit.
export function mergeRequests(existing, incoming, max) {
    const byId = new Map(existing.map((req) => [req.id, req]));
    incoming.forEach((req) => byId.set(req.id, req));
    return [...byId.values()].sort((a, b) => b.id - a.id).slice(0, max);
}
