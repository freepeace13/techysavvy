// Pure helpers for the upload flow — no DOM, so they can be tested directly.

const ALLOWED = ['docx', 'pdf'];

export function formatSize(bytes) {
    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1).replace(/\.0$/, '')} MB`;
    }

    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

/** Returns an error message, or null when the file is acceptable to upload. */
export function validateFile(file, { maxBytes }) {
    const extension = (file.name.split('.').pop() ?? '').toLowerCase();

    if (!ALLOWED.includes(extension)) {
        return 'Only .docx and .pdf files can be converted.';
    }

    if (maxBytes && file.size > maxBytes) {
        return `That file is larger than the ${formatSize(maxBytes)} upload limit.`;
    }

    return null;
}

/** Turns a failed HTTP response into a message a person can act on. */
export function messageForFailure(status, body) {
    const specific = body?.errors?.file?.[0] ?? body?.message;

    if (specific && status === 422) return specific;

    switch (status) {
        case 413: return 'That file is too large for the server to accept.';
        case 419: return 'Your session expired. Reload the page and try again.';
        case 429: return 'Too many conversions in a short time. Wait a minute and try again.';
    }

    if (status >= 500) return 'The server ran into a problem converting that file. Try again in a moment.';

    return specific ?? 'That file could not be converted.';
}
