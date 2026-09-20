import { test } from 'node:test';
import assert from 'node:assert/strict';
import { formatSize, messageForFailure, validateFile } from '../../resources/js/upload.js';

const file = (name, size = 1000) => ({ name, size });

test('validateFile accepts docx and pdf regardless of case', () => {
    assert.equal(validateFile(file('a.DOCX'), { maxBytes: 5000 }), null);
    assert.equal(validateFile(file('a.pdf'), { maxBytes: 5000 }), null);
});

test('validateFile rejects other types', () => {
    assert.match(validateFile(file('a.txt'), { maxBytes: 5000 }), /Only \.docx and \.pdf/);
    assert.match(validateFile(file('noextension'), { maxBytes: 5000 }), /Only \.docx and \.pdf/);
});

test('validateFile rejects an oversize file before upload', () => {
    assert.match(validateFile(file('a.pdf', 6 * 1024 * 1024), { maxBytes: 5 * 1024 * 1024 }), /larger than the 5 MB/);
});

test('formatSize', () => {
    assert.equal(formatSize(10 * 1024 * 1024), '10 MB');
    assert.equal(formatSize(1536 * 1024), '1.5 MB');
    assert.equal(formatSize(500 * 1024), '500 KB');
});

test('messageForFailure prefers the server message on 422', () => {
    assert.equal(messageForFailure(422, { errors: { file: ['Bad file'] } }), 'Bad file');
    assert.equal(messageForFailure(422, { message: 'Nope' }), 'Nope');
});

test('messageForFailure explains 413, 419, 429 and 5xx even without a JSON body', () => {
    assert.match(messageForFailure(413, null), /too large/);
    assert.match(messageForFailure(419, null), /session expired/);
    assert.match(messageForFailure(429, null), /Too many/);
    assert.match(messageForFailure(500, null), /server ran into a problem/);
});
