// Parses dotenv text into entries. Pure and DOM-free so it can be unit tested.
// Nothing here touches the network or storage: input stays in memory.

const LINE = /^\s*(?:export\s+)?([A-Za-z_][A-Za-z0-9_.-]*)\s*=(.*)$/;

function unquote(raw) {
    const value = raw.trim();
    const quote = value[0];

    if (quote === '"' || quote === "'") {
        // Find the closing quote, honouring backslash escapes in double quotes.
        for (let i = 1; i < value.length; i++) {
            if (quote === '"' && value[i] === '\\') {
                i++;
                continue;
            }
            if (value[i] === quote) {
                return value.slice(1, i);
            }
        }
        return value.slice(1); // unterminated: take the rest
    }

    // Unquoted: an inline comment starts at " #".
    const hash = value.search(/\s#/);

    return (hash === -1 ? value : value.slice(0, hash)).trim();
}

/**
 * @returns {{
 *   entries: {key: string, value: string, line: number}[],
 *   map: Map<string, {value: string, line: number}>,
 *   duplicates: string[],
 *   invalid: {line: number, text: string}[],
 * }}
 */
export function parseEnv(text) {
    const entries = [];
    const map = new Map();
    const duplicates = new Set();
    const invalid = [];

    String(text ?? '')
        .split(/\r?\n/)
        .forEach((rawLine, index) => {
            const line = index + 1;
            const trimmed = rawLine.trim();

            if (trimmed === '' || trimmed.startsWith('#')) {
                return;
            }

            const match = LINE.exec(rawLine);
            if (!match) {
                invalid.push({ line, text: trimmed });
                return;
            }

            const key = match[1];
            const value = unquote(match[2]);

            if (map.has(key)) {
                duplicates.add(key);
            }

            entries.push({ key, value, line });
            map.set(key, { value, line }); // last one wins, like dotenv
        });

    return { entries, map, duplicates: [...duplicates], invalid };
}
