import { test, beforeEach } from 'node:test';
import assert from 'node:assert/strict';
import { qrForge } from '../../resources/js/component.js';
import { fakeContext } from './support.js';

function stub(name, value) {
    Object.defineProperty(globalThis, name, { value, configurable: true, writable: true });
}

let downloads;
let canvases;

// Browser surface the component touches: FileReader + Image for the logo,
// canvas/anchor/object URLs for downloads.
beforeEach(() => {
    downloads = [];
    canvases = [];

    stub('FileReader', class {
        readAsDataURL(file) {
            queueMicrotask(() => {
                if (file.unreadable) return this.onerror?.();
                this.result = `data:${file.type};base64,TE9HTw==`;
                this.onload?.();
            });
        }
    });
    stub('Image', class {
        set src(value) {
            this._src = value;
            queueMicrotask(() => (value.includes('broken') ? this.onerror?.() : (this.width = 200, this.height = 100, this.onload?.())));
        }
        get src() { return this._src; }
    });
    stub('URL', { createObjectURL: (blob) => { downloads.push({ blob }); return 'blob:x'; }, revokeObjectURL() {} });
    stub('document', {
        createElement(tag) {
            if (tag === 'canvas') {
                const canvas = {
                    width: 0,
                    height: 0,
                    getContext() { return (canvas.fake ??= fakeContext(canvas.width, canvas.height)).ctx; },
                    toBlob(callback, type) { callback(new Blob(['png'], { type })); },
                };
                canvases.push(canvas);
                return canvas;
            }
            return { click() { downloads.at(-1).filename = this.download; } };
        },
    });
});

function component(text = '') {
    const c = qrForge();
    c.text = text;
    c.render();

    return c;
}

function file(name, type) {
    return { name, type };
}

test('empty or whitespace-only text shows no code and no error', () => {
    for (const text of ['', '   \n']) {
        const c = component(text);
        assert.equal(c.svg, '');
        assert.equal(c.errorMessage, '');
        assert.equal(c.canDownload, false);
    }
});

test('text renders an SVG preview and enables downloads', () => {
    const c = component('https://example.com');

    assert.match(c.svg, /^<svg /);
    assert.equal(c.canDownload, true);
});

test('text that is too long shows an error instead of a code', () => {
    const c = component('x'.repeat(3000));

    assert.equal(c.svg, '');
    assert.match(c.errorMessage, /too long/);
    assert.equal(c.canDownload, false);
});

test('clearing too-long text clears the error', () => {
    const c = component('x'.repeat(3000));
    c.text = 'ok';
    c.render();

    assert.equal(c.errorMessage, '');
});

test('choosing a non-image logo is rejected with a message', async () => {
    const c = component('hello');
    await c.setLogo(file('notes.txt', 'text/plain'));

    assert.equal(c.logo, null);
    assert.match(c.errorMessage, /not an image/);
    assert.doesNotMatch(c.svg, /<image/);
});

test('an image logo is overlaid and forces error correction H', async () => {
    const c = component('hello');
    await c.setLogo(file('logo.png', 'image/png'));

    assert.equal(c.errorMessage, '');
    assert.equal(c.file.name, 'logo.png');
    assert.equal(c.level, 'H');
    assert.match(c.svg, /<image [^>]*href="data:image\/png;base64,TE9HTw=="/);
});

test('an unreadable logo shows an error and keeps the code logo-free', async () => {
    const c = component('hello');
    await c.setLogo({ name: 'x.png', type: 'image/png', unreadable: true });

    assert.equal(c.logo, null);
    assert.match(c.errorMessage, /could not be read/);
    assert.equal(c.level, 'M');
});

test('a dropped logo is picked up from the drop event', async () => {
    const c = component('hello');
    c.dragging = true;
    await c.handleDrop({ dataTransfer: { files: [file('logo.png', 'image/png')] } });

    assert.equal(c.dragging, false);
    assert.equal(c.level, 'H');
});

test('removing the logo goes back to error correction M', async () => {
    const c = component('hello');
    await c.setLogo(file('logo.png', 'image/png'));
    c.removeLogo();

    assert.equal(c.file, null);
    assert.equal(c.level, 'M');
    assert.doesNotMatch(c.svg, /<image/);
});

test('downloadSvg saves the preview as qr-forge.svg', async () => {
    const c = component('hello');
    c.downloadSvg();

    assert.equal(downloads.length, 1);
    assert.equal(downloads[0].filename, 'qr-forge.svg');
    assert.equal(downloads[0].blob.type, 'image/svg+xml');
    assert.equal(await downloads[0].blob.text(), c.svg);
});

test('downloadPng renders the chosen size and saves qr-forge.png', () => {
    const c = component('hello');
    c.pngSize = 1024;
    c.downloadPng();

    assert.equal(canvases[0].width, 1024);
    assert.equal(canvases[0].height, 1024);
    assert.equal(downloads[0].filename, 'qr-forge.png');
    assert.equal(downloads[0].blob.type, 'image/png');
});

test('downloadPng draws the logo into the PNG', async () => {
    const c = component('hello');
    await c.setLogo(file('logo.png', 'image/png'));
    c.downloadPng();

    assert.equal(canvases[0].fake.drawn.length, 1);
    assert.equal(canvases[0].fake.drawn[0].source, c.logo);
});

test('downloads do nothing while there is no code', () => {
    const c = component('');
    c.downloadSvg();
    c.downloadPng();

    assert.equal(downloads.length, 0);
});
