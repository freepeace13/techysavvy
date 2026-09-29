import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

test('the built bundle exposes the qrForge Alpine component on window', () => {
    const source = readFileSync(new URL('../../resources/dist/qr-forge.js', import.meta.url), 'utf8');
    const window = {};
    vm.runInContext(source, vm.createContext({ window, self: window, globalThis: window, TextEncoder }));

    const component = window.qrForge();
    component.text = 'https://example.com';
    component.render();

    assert.match(component.svg, /^<svg /);
});
