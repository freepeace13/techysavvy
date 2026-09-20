import { test } from 'node:test';
import assert from 'node:assert/strict';
import { parseEnv } from '../../resources/js/parse.js';

test('parses plain, exported, quoted and commented values', () => {
    const { map, invalid } = parseEnv([
        '# comment',
        'A=1',
        'export B="two words" # trailing',
        "C='single # not comment'",
        'D=value # inline comment',
        'E=',
        '',
    ].join('\n'));

    assert.equal(map.get('A').value, '1');
    assert.equal(map.get('B').value, 'two words');
    assert.equal(map.get('C').value, 'single # not comment');
    assert.equal(map.get('D').value, 'value');
    assert.equal(map.get('E').value, '');
    assert.deepEqual(invalid, []);
});

test('reports duplicates (last wins) and invalid lines with line numbers', () => {
    const parsed = parseEnv('A=1\nnot a line\nA=2\r\n');

    assert.deepEqual(parsed.duplicates, ['A']);
    assert.equal(parsed.map.get('A').value, '2');
    assert.deepEqual(parsed.invalid, [{ line: 2, text: 'not a line' }]);
});

test('handles escaped quotes and empty input', () => {
    assert.equal(parseEnv('A="say \\"hi\\""').map.get('A').value, 'say \\"hi\\"');
    assert.equal(parseEnv('').map.size, 0);
    assert.equal(parseEnv(null).map.size, 0);
});
