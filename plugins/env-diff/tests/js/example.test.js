import { test } from 'node:test';
import assert from 'node:assert/strict';
import { generateExample } from '../../resources/js/example.js';

test('strips values but keeps order, comments and blank lines', () => {
    const out = generateExample('# App\nAPP_NAME="My App"\n\nexport DB_PASS=s3cret # note\n');

    assert.equal(out, '# App\nAPP_NAME=\n\nexport DB_PASS=\n');
});

test('drops duplicate keys and junk lines, never echoes values', () => {
    const out = generateExample('A=1\nA=2\ngarbage value\nB=topsecret');

    assert.equal(out, 'A=\nB=\n');
    assert.equal(out.includes('topsecret'), false);
});

test('empty input yields a single newline', () => {
    assert.equal(generateExample(''), '\n');
});
