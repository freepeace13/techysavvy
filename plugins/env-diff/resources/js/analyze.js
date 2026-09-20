// Diff and heuristics over parsed env files. Findings never carry a full
// value, only a masked preview, so results are safe to render and screenshot.

const PLACEHOLDER = /^(change[-_]?me|changeit|todo|tbd|fixme|xxx+|\*{3,}|your[-_ ].*|<.*>|example|placeholder|secret|password|null|none|dummy|replace[-_ ]?me)$/i;

// Well-known credential shapes. Deliberately narrow to keep false positives low.
const SECRET_PATTERNS = [
    { label: 'AWS access key ID', test: /^(AKIA|ASIA)[0-9A-Z]{16}$/ },
    { label: 'Stripe secret key', test: /^(sk|rk)_(live|test)_[0-9A-Za-z]{16,}$/ },
    { label: 'GitHub token', test: /^(ghp|gho|ghu|ghs|ghr|github_pat)_[0-9A-Za-z_]{20,}$/ },
    { label: 'Slack token', test: /^xox[abprs]-[0-9A-Za-z-]{10,}$/ },
    { label: 'Google API key', test: /^AIza[0-9A-Za-z_-]{35}$/ },
    { label: 'SendGrid key', test: /^SG\.[0-9A-Za-z_-]{16,}\.[0-9A-Za-z_-]{16,}$/ },
    { label: 'Private key block', test: /-----BEGIN [A-Z ]*PRIVATE KEY-----/ },
    { label: 'Laravel APP_KEY', test: /^base64:[0-9A-Za-z+/]{43}=$/ },
    { label: 'Credentials in URL', test: /^[a-z][a-z0-9+.-]*:\/\/[^\s:@/]+:[^\s@/]+@/i },
];

const SENSITIVE_KEY = /(SECRET|TOKEN|PASSWORD|PASSWD|PRIVATE|API_?KEY|ACCESS_?KEY|CREDENTIAL|SIGNING)/i;

export function isEmpty(value) {
    return value === '';
}

export function isPlaceholder(value) {
    return value !== '' && PLACEHOLDER.test(value.trim());
}

export function shannonEntropy(value) {
    if (!value) {
        return 0;
    }

    const counts = new Map();
    for (const char of value) {
        counts.set(char, (counts.get(char) ?? 0) + 1);
    }

    let entropy = 0;
    for (const count of counts.values()) {
        const p = count / value.length;
        entropy -= p * Math.log2(p);
    }

    return entropy;
}

export function mask(value) {
    if (value.length <= 8) {
        return '•'.repeat(Math.max(value.length, 4));
    }

    return `${value.slice(0, 2)}${'•'.repeat(6)}${value.slice(-2)}`;
}

/** Why a value looks like a real secret, or null. */
export function secretReason(key, value) {
    if (value === '' || isPlaceholder(value)) {
        return null;
    }

    for (const { label, test } of SECRET_PATTERNS) {
        if (test.test(value)) {
            return label;
        }
    }

    if (value.length >= 20 && !/\s/.test(value) && shannonEntropy(value) >= 3.5
        && /[A-Za-z]/.test(value) && /[0-9]/.test(value)) {
        return 'High-entropy value';
    }

    if (SENSITIVE_KEY.test(key) && value.length >= 12 && !/\s/.test(value)
        && !/^(true|false|\d+)$/i.test(value)) {
        return 'Sensitive key name with a non-trivial value';
    }

    return null;
}

/** Key-level diff. `a` and `b` are parseEnv() results. */
export function diffEnvs(a, b) {
    const onlyInA = [];
    const onlyInB = [];
    const inBoth = [];

    for (const key of a.map.keys()) {
        (b.map.has(key) ? inBoth : onlyInA).push(key);
    }
    for (const key of b.map.keys()) {
        if (!a.map.has(key)) {
            onlyInB.push(key);
        }
    }

    return { onlyInA, onlyInB, inBoth };
}

/** Empty, placeholder and secret findings for one parsed file. */
export function inspectEnv(parsed) {
    const empty = [];
    const placeholders = [];
    const secrets = [];

    for (const [key, { value }] of parsed.map) {
        if (isEmpty(value)) {
            empty.push(key);
        } else if (isPlaceholder(value)) {
            placeholders.push(key);
        } else {
            const reason = secretReason(key, value);
            if (reason) {
                secrets.push({ key, reason, preview: mask(value) });
            }
        }
    }

    return { empty, placeholders, secrets };
}
