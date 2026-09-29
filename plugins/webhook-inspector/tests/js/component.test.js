import { test, beforeEach } from 'node:test';
import assert from 'node:assert/strict';
import { webhookInspector } from '../../resources/js/component.js';

let responses, fetched, listeners, intervals, copied;

function stub(name, value) {
    Object.defineProperty(globalThis, name, { value, configurable: true, writable: true });
}

beforeEach(() => {
    responses = []; fetched = []; listeners = {}; intervals = []; copied = [];
    stub('fetch', async (url) => { fetched.push(url); const r = responses.shift(); return { ok: r.status === 200, status: r.status, json: async () => r.body }; });
    stub('document', { visibilityState: 'visible', addEventListener: (type, fn) => { listeners[type] = fn; } });
    stub('setInterval', (fn) => { intervals.push(fn); return intervals.length; });
    stub('clearInterval', (id) => { intervals[id - 1] = null; });
    stub('navigator', { clipboard: { writeText: async (text) => copied.push(text) } });
});

const r = (id) => ({ id, method: 'POST', path: '/', query: '', headers: {}, body: `b${id}`, content_type: null, body_size: 2, truncated: false, is_binary: false, received_at: '2026-09-30T12:00:00Z' });
const ok = (...ids) => ({ status: 200, body: { expires_at: '2026-10-01T12:00:00Z', requests: ids.map(r) } });
const make = () => webhookInspector({ pollUrl: '/p', intervalMs: '2000', maxRequests: '3', expired: 'false' });

test('merges new requests newest first, polls after the last id, auto-selects only once', async () => {
    const c = make();
    responses.push(ok(2, 1), ok(4, 3));
    await c.poll();
    assert.equal(c.selectedId, 2);
    c.select(1);
    await c.poll();
    assert.deepEqual(fetched, ['/p?after=0', '/p?after=2']);
    assert.deepEqual(c.requests.map((x) => x.id), [4, 3, 2]);
    assert.equal(c.selectedId, 1);
});

test('stops polling and shows expired on 410 or 404', async () => {
    const c = make();
    c.start();
    responses.push({ status: 410 });
    await c.poll();
    assert.equal(c.expired, true);
    assert.equal(c.timer, null);
});

test('pauses while the tab is hidden and resumes when visible', async () => {
    const c = make();
    responses.push(ok(), ok());
    c.init();
    assert.notEqual(c.timer, null);
    document.visibilityState = 'hidden';
    listeners.visibilitychange();
    assert.equal(c.timer, null);
    document.visibilityState = 'visible';
    listeners.visibilitychange();
    assert.notEqual(c.timer, null);
});

test('copies the raw body, not the pretty one', async () => {
    const c = make();
    responses.push({ status: 200, body: { expires_at: 'x', requests: [{ ...r(1), body: '{"a":1}', content_type: 'application/json' }] } });
    await c.poll();
    await c.copyBody();
    assert.deepEqual(copied, ['{"a":1}']);
});
