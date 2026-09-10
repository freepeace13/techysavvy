import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

function loadBundle() {
    const source = readFileSync(
        new URL('../../resources/dist/doc-to-markdown.js', import.meta.url),
        'utf8',
    );

    const window = {};
    const context = vm.createContext({ window, self: window, globalThis: window });
    vm.runInContext(source, context);

    return window;
}

test('the built bundle exposes docToMarkdownRender on window', () => {
    const window = loadBundle();

    assert.equal(typeof window.docToMarkdownRender, 'function');
});

test('it renders Markdown to HTML', () => {
    const { docToMarkdownRender } = loadBundle();

    assert.equal(docToMarkdownRender('# Hello').trim(), '<h1>Hello</h1>');
});

test('it renders a table, proving markdown-it is bundled in', () => {
    const { docToMarkdownRender } = loadBundle();

    const html = docToMarkdownRender('| a | b |\n| - | - |\n| 1 | 2 |');

    assert.match(html, /<table>/);
    assert.match(html, /<td>1<\/td>/);
});

test('raw HTML in the source is escaped, not passed through', () => {
    const { docToMarkdownRender } = loadBundle();

    const html = docToMarkdownRender('<script>alert(1)</script>');

    assert.doesNotMatch(html, /<script>/);
});
