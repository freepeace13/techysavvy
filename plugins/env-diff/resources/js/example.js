// Builds a .env.example from raw text: same order, comments and blank lines,
// every value stripped. Lines that aren't KEY=value are kept verbatim only if
// they are comments/blank; anything else is dropped so nothing odd leaks.

const LINE = /^(\s*(?:export\s+)?[A-Za-z_][A-Za-z0-9_.-]*\s*=)/;

export function generateExample(text) {
    const seen = new Set();
    const out = [];

    for (const raw of String(text ?? '').split(/\r?\n/)) {
        const trimmed = raw.trim();

        if (trimmed === '' || trimmed.startsWith('#')) {
            out.push(raw);
            continue;
        }

        const match = LINE.exec(raw);
        if (!match) {
            continue;
        }

        const key = match[1].replace(/^\s*(export\s+)?/, '').replace(/\s*=$/, '');
        if (seen.has(key)) {
            continue;
        }
        seen.add(key);

        out.push(`${match[1].trimStart()}`);
    }

    return out.join('\n').replace(/\n{3,}/g, '\n\n').replace(/\s+$/, '') + '\n';
}
