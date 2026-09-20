import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

function loadBundle() {
    const source = readFileSync(new URL('../../resources/dist/env-diff.js', import.meta.url), 'utf8');
    const window = {};
    vm.runInContext(source, vm.createContext({ window, self: window, globalThis: window }));

    return window;
}

test('the built bundle exposes envDiff on window', () => {
    assert.equal(typeof loadBundle().envDiff, 'function');
});

test('the bundle never touches the network or browser storage', () => {
    const source = readFileSync(new URL('../../resources/dist/env-diff.js', import.meta.url), 'utf8');

    for (const forbidden of ['fetch(', 'XMLHttpRequest', 'sendBeacon', 'WebSocket', 'localStorage', 'sessionStorage', 'indexedDB', 'document.cookie']) {
        assert.equal(source.includes(forbidden), false, `bundle must not use ${forbidden}`);
    }
});

test('analyze() computes a result and clears on empty input', () => {
    const c = loadBundle().envDiff();

    c.a = 'A=1\nSECRET_TOKEN=aB3xK9mQ2zR7vL4pW8nT5yU1';
    c.b = 'B=';
    c.analyze();

    assert.deepEqual([...c.result.diff.onlyInA], ['A', 'SECRET_TOKEN']);
    assert.deepEqual([...c.result.diff.onlyInB], ['B']);
    assert.equal(c.result.a.secrets.length, 1);
    assert.equal(c.example, 'A=\nSECRET_TOKEN=\n');

    c.clear();
    c.analyze();
    assert.equal(c.result, null);
});
