import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createQr, drawToCanvas, logoBox, QrTooLongError, QUIET_ZONE, toSvg } from '../../resources/js/qr.js';
import { decode, fakeContext, rasterizeCells } from './support.js';

function cellsFromSvg(svg) {
    const path = svg.match(/<path[^>]* d="([^"]+)"/)[1];

    return [...path.matchAll(/M(\d+) (\d+)h1v1h-1z/g)].map(([, x, y]) => [Number(x), Number(y)]);
}

function scanSvg(svg) {
    const count = Number(svg.match(/viewBox="0 0 (\d+) \1"/)[1]);

    return decode(rasterizeCells(cellsFromSvg(svg), count));
}

function scanCanvas(qr, px, logo = null) {
    const { ctx, image, drawn } = fakeContext(px, px);
    drawToCanvas(ctx, qr, px, logo);

    return { text: decode(image), drawn };
}

test('a URL round-trips through the SVG', () => {
    const text = 'https://example.com/some/path?q=1';

    assert.equal(scanSvg(toSvg(createQr(text))), text);
});

test('non-ASCII text is encoded as UTF-8, not truncated to Latin-1', () => {
    const text = 'Grüße 👋 日本語';

    assert.equal(scanSvg(toSvg(createQr(text))), text);
});

test('error correction is M by default and H when a logo will be overlaid', () => {
    assert.equal(createQr('hello').level, 'M');
    assert.equal(createQr('hello', { withLogo: true }).level, 'H');
});

test('text beyond QR capacity throws QrTooLongError', () => {
    assert.throws(() => createQr('x'.repeat(3000), { withLogo: true }), QrTooLongError);
});

test('the SVG has a quiet zone of 4 modules around the code', () => {
    const qr = createQr('hello');
    const svg = toSvg(qr);
    const cells = cellsFromSvg(svg);

    assert.match(svg, new RegExp(`viewBox="0 0 ${qr.size + 2 * QUIET_ZONE} ${qr.size + 2 * QUIET_ZONE}"`));
    assert.equal(Math.min(...cells.map(([x]) => x)), QUIET_ZONE);
    assert.equal(Math.min(...cells.map(([, y]) => y)), QUIET_ZONE);
});

test('the SVG embeds the logo on a white backing centered on the code', () => {
    const qr = createQr('hello', { withLogo: true });
    const box = logoBox(qr.size);
    const svg = toSvg(qr, { logoDataUrl: 'data:image/png;base64,AAAA' });

    assert.match(svg, /<image [^>]*href="data:image\/png;base64,AAAA"/);
    assert.match(svg, new RegExp(`<rect x="${box.x - box.pad}" y="${box.y - box.pad}" width="${box.size + 2 * box.pad}" height="${box.size + 2 * box.pad}" fill="#ffffff"`));
});

test('logo data URLs cannot break out of the SVG attribute', () => {
    const svg = toSvg(createQr('hello', { withLogo: true }), { logoDataUrl: 'data:x"/><script>alert(1)</script>' });

    assert.doesNotMatch(svg, /<script/);
});

test('logoBox is centered and covers 26-30% of the code width', () => {
    for (const size of [37, 45, 57, 101, 177]) {
        const box = logoBox(size);
        const total = size + 2 * QUIET_ZONE;
        const share = (box.size + 2 * box.pad) / size;

        assert.equal(box.x + box.size / 2, total / 2);
        assert.equal(box.y, box.x);
        assert.ok(share > 0.26 && share <= 0.3, `size ${size}: ${share}`);
    }
});

test('codes with a logo still scan across the range of QR versions', () => {
    for (const length of [2, 40, 120, 300, 700, 1200]) {
        const text = 'https://example.com/?' + 'q'.repeat(length);
        const { text: decoded } = scanCanvas(createQr(text, { withLogo: true }), 1400, { width: 100, height: 100 });

        assert.equal(decoded, text, `length ${length}`);
    }
});

test('the canvas render is exactly the requested pixel size and scans', () => {
    const text = 'https://example.com';

    for (const px of [256, 512, 1000]) {
        assert.equal(scanCanvas(createQr(text), px).text, text, `px ${px}`);
    }
});

test('a canvas render with a logo overlaid still scans', () => {
    const text = 'https://example.com/with-a-logo';
    const logo = { width: 300, height: 150 };
    const { text: decoded, drawn } = scanCanvas(createQr(text, { withLogo: true }), 512, logo);

    assert.equal(drawn.length, 1);
    assert.equal(drawn[0].source, logo);
    assert.equal(decoded, text);
});

test('a logo is drawn inside its box, keeping its aspect ratio', () => {
    const qr = createQr('hello', { withLogo: true });
    const { drawn } = scanCanvas(qr, 580, { width: 300, height: 150 });

    assert.ok(Math.abs(drawn[0].w / drawn[0].h - 2) < 0.01);
});

test('an SVG with a logo overlaid still scans once the logo area is blanked', () => {
    const text = 'https://example.com/with-a-logo';
    const qr = createQr(text, { withLogo: true });
    const box = logoBox(qr.size);
    const inLogo = ([x, y]) => x >= box.x - box.pad && x < box.x + box.size + box.pad
        && y >= box.y - box.pad && y < box.y + box.size + box.pad;
    const cells = cellsFromSvg(toSvg(qr)).filter((cell) => !inLogo(cell));

    assert.equal(decode(rasterizeCells(cells, qr.size + 2 * QUIET_ZONE)), text);
});

test('short text under a logo is bumped to version 5 so the logo has room', () => {
    assert.equal(createQr('hi').size, 21);
    assert.equal(createQr('hi', { withLogo: true }).size, 37);
});

test('the logo keeps a white margin of at least one module from the code', () => {
    for (const size of [37, 57, 177]) {
        assert.ok(logoBox(size).pad >= 1);
        assert.ok(logoBox(size).size >= 3, `size ${size}`);
    }
});
