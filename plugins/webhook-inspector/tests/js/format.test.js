import { test } from 'node:test';
import assert from 'node:assert/strict';
import { bodyView, formatBytes, relativeTime, expiresIn, queryRows, headerRows, truncationNotice } from '../../resources/js/format.js';

const req = (over = {}) => ({ body: '', content_type: null, body_size: 0, is_binary: false, truncated: false, ...over });

test('pretty-prints JSON by content type or by parse', () => {
    assert.equal(bodyView(req({ body: '{"a":1}', content_type: 'application/json' })).text, '{\n  "a": 1\n}');
    assert.equal(bodyView(req({ body: '[1]', content_type: 'text/plain' })).text, '[\n  1\n]');
    assert.equal(bodyView(req({ body: '{bad', content_type: 'application/json' })).text, '{bad');
});

test('raw text stays raw, including markup', () => {
    const view = bodyView(req({ body: '<script>alert(1)</script>', content_type: 'text/html' }));
    assert.deepEqual(view, { kind: 'text', text: '<script>alert(1)</script>', copyable: true });
});

test('empty and binary bodies', () => {
    assert.deepEqual(bodyView(req()), { kind: 'empty', text: '(empty body)', copyable: false });
    assert.deepEqual(bodyView(req({ body: '//4AAQ==', is_binary: true, body_size: 4 })), { kind: 'binary', text: 'binary, 4 B', copyable: false });
});

test('truncation notice', () => {
    assert.equal(truncationNotice(req({ truncated: true, body_size: 600 * 1024 }), 256), 'Truncated: showing first 256 KB of 600 KB');
    assert.equal(truncationNotice(req(), 256), null);
});

test('sizes and times', () => {
    assert.equal(formatBytes(0), '0 B');
    assert.equal(formatBytes(1536), '1.5 KB');
    assert.equal(formatBytes(2 * 1024 * 1024), '2.0 MB');
    const now = Date.parse('2026-09-30T12:00:00Z');
    assert.equal(relativeTime('2026-09-30T11:59:58Z', now), 'just now');
    assert.equal(relativeTime('2026-09-30T11:59:48Z', now), '12s ago');
    assert.equal(relativeTime('2026-09-30T11:57:00Z', now), '3m ago');
    assert.equal(relativeTime('2026-09-30T10:00:00Z', now), '2h ago');
    assert.equal(expiresIn('2026-10-01T11:48:00Z', now), 'Expires in 23h 48m');
    assert.equal(expiresIn('2026-09-30T12:05:00Z', now), 'Expires in 5m');
    assert.equal(expiresIn('2026-09-30T11:00:00Z', now), 'Expired');
});

test('query and header rows', () => {
    assert.deepEqual(queryRows('a=1&tag=x&tag=y'), [['a', '1'], ['tag', 'x'], ['tag', 'y']]);
    assert.deepEqual(queryRows(''), []);
    assert.deepEqual(headerRows({ 'x-a': ['1', '2'], host: ['h'] }), [['host', 'h'], ['x-a', '1'], ['x-a', '2']]);
});
