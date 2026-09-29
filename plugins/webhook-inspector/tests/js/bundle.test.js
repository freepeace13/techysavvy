import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

test('the built bundle exposes the webhookInspector Alpine component on window', () => {
    const source = readFileSync(new URL('../../resources/dist/webhook-inspector.js', import.meta.url), 'utf8');
    const window = {};
    vm.runInContext(source, vm.createContext({ window, self: window, globalThis: window }));

    const component = window.webhookInspector({ pollUrl: '/p', expired: 'false' });

    assert.equal(typeof component.poll, 'function');
    assert.equal(component.pollUrl, '/p');
});
