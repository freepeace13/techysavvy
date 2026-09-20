import { test } from 'node:test';
import assert from 'node:assert/strict';
import { parseEnv } from '../../resources/js/parse.js';
import { diffEnvs, inspectEnv, secretReason, mask, isPlaceholder } from '../../resources/js/analyze.js';

test('diffs keys into only-in-a, only-in-b and shared', () => {
    const diff = diffEnvs(parseEnv('A=1\nB=2'), parseEnv('B=\nC='));

    assert.deepEqual(diff.onlyInA, ['A']);
    assert.deepEqual(diff.onlyInB, ['C']);
    assert.deepEqual(diff.inBoth, ['B']);
});

test('recognises placeholders', () => {
    for (const v of ['changeme', 'CHANGE_ME', 'xxx', 'your-api-key', '<token>', 'TODO']) {
        assert.equal(isPlaceholder(v), true, v);
    }
    assert.equal(isPlaceholder('production'), false);
    assert.equal(isPlaceholder(''), false);
});

test('flags known credential shapes', () => {
    assert.equal(secretReason('X', 'AKIAIOSFODNN7EXAMPLE'), 'AWS access key ID');
    assert.equal(secretReason('X', 'sk_live_' + 'a'.repeat(24)), 'Stripe secret key');
    assert.equal(secretReason('DB_URL', 'postgres://user:hunter2@db/app'), 'Credentials in URL');
});

test('flags high-entropy values and sensitive key names, not ordinary config', () => {
    assert.equal(secretReason('X', 'aB3xK9mQ2zR7vL4pW8nT5yU1'), 'High-entropy value');
    assert.equal(secretReason('MAIL_PASSWORD', 'correcthorsebattery'), 'Sensitive key name with a non-trivial value');
    assert.equal(secretReason('APP_NAME', 'My Application'), null);
    assert.equal(secretReason('APP_ENV', 'production'), null);
    assert.equal(secretReason('APP_DEBUG', 'true'), null);
    assert.equal(secretReason('API_KEY', 'changeme'), null);
});

test('inspectEnv sorts findings and never returns a full secret', () => {
    const value = 'sk_live_' + 'a'.repeat(24);
    const report = inspectEnv(parseEnv(`A=\nB=changeme\nC=${value}\nD=ok`));

    assert.deepEqual(report.empty, ['A']);
    assert.deepEqual(report.placeholders, ['B']);
    assert.equal(report.secrets.length, 1);
    assert.equal(report.secrets[0].key, 'C');
    assert.equal(JSON.stringify(report).includes(value), false);
});

test('mask hides the middle and short values entirely', () => {
    assert.equal(mask('abcdefghijkl'), 'ab••••••kl');
    assert.equal(mask('abc'), '••••');
});
