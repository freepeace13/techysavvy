import { test } from 'node:test';
import assert from 'node:assert/strict';
import { docToMarkdown } from '../../resources/js/component.js';

function stub(name, value) {
    Object.defineProperty(globalThis, name, { value, configurable: true, writable: true });
}

function fakeDocument({ copyResult }) {
    const appended = [];
    stub('document', {
        createElement: () => ({ setAttribute() {}, style: {}, select() {}, remove() { appended.pop(); } }),
        body: { appendChild: (el) => appended.push(el) },
        execCommand: () => copyResult,
    });

    return appended;
}

function component() {
    const c = docToMarkdown({ action: '/x', maxBytes: 1000 });
    c.markdown = '# Hi';
    c.markdownHtml = '<h1>Hi</h1>';

    return c;
}

test('copy writes html and plain text when the async clipboard API is available', async () => {
    let written;
    stub('navigator', { clipboard: { write: async (items) => { written = items; } } });
    stub('ClipboardItem', class { constructor(data) { this.data = data; } });
    stub('window', globalThis);

    const c = component();
    await c.copy();

    assert.equal(c.copied, true);
    assert.deepEqual(Object.keys(written[0].data), ['text/html', 'text/plain']);
});

test('copy falls back to the legacy command when navigator.clipboard is missing (non-HTTPS)', async () => {
    stub('navigator', {});
    stub('ClipboardItem', undefined);
    fakeDocument({ copyResult: true });

    const c = component();
    await c.copy();

    assert.equal(c.copied, true);
    assert.equal(c.copyFailed, false);
});

test('copy reports failure instead of throwing when every path is refused', async () => {
    stub('navigator', { clipboard: { writeText: async () => { throw new Error('denied'); } } });
    stub('ClipboardItem', undefined);
    fakeDocument({ copyResult: false });

    const c = component();
    await c.copy();

    assert.equal(c.copied, false);
    assert.equal(c.copyFailed, true);
});

test('showResult falls back to escaped plain text if the renderer throws', () => {
    stub('window', { docToMarkdownRender() { throw new Error('bundle broken'); } });

    const c = component();
    c.showResult({ markdown: '<b>x</b>', filename: 'a.md' });

    assert.equal(c.state, 'success');
    assert.equal(c.markdownHtml, '<pre>&lt;b&gt;x&lt;/b&gt;</pre>');
});
