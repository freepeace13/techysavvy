import jsQR from 'jsqr';

// A white RGBA bitmap the fakes below paint into, so tests can decode what the
// production code actually drew instead of asserting on its call pattern.
export function bitmap(width, height) {
    const data = new Uint8ClampedArray(width * height * 4).fill(255);

    return {
        width,
        height,
        data,
        fill(x, y, w, h, gray) {
            for (let row = Math.max(0, y); row < Math.min(height, y + h); row++) {
                for (let col = Math.max(0, x); col < Math.min(width, x + w); col++) {
                    const i = (row * width + col) * 4;
                    data[i] = data[i + 1] = data[i + 2] = gray;
                }
            }
        },
    };
}

export function decode({ data, width, height }) {
    return jsQR(data, width, height)?.data ?? null;
}

// Renders a module grid at a fixed cell size — used to scan the SVG path.
export function rasterizeCells(cells, count, cell = 8) {
    const image = bitmap(count * cell, count * cell);
    for (const [x, y] of cells) image.fill(x * cell, y * cell, cell, cell, 0);

    return image;
}

// Minimal stand-in for CanvasRenderingContext2D covering what the renderer
// uses. fillRect only accepts integer rects: a fractional one would anti-alias
// into grey seams on a real canvas.
export function fakeContext(width, height) {
    const image = bitmap(width, height);
    const drawn = [];

    const ctx = {
        fillStyle: '#000000',
        imageSmoothingEnabled: true,
        fillRect(x, y, w, h) {
            for (const n of [x, y, w, h]) {
                if (!Number.isInteger(n)) throw new Error(`fillRect got a fractional value: ${n}`);
            }
            image.fill(x, y, w, h, ctx.fillStyle.toLowerCase() === '#ffffff' ? 255 : 0);
        },
        // Paint the logo as solid mid-grey: it occludes modules like a real logo.
        drawImage(source, x, y, w, h) {
            drawn.push({ source, x, y, w, h });
            image.fill(Math.round(x), Math.round(y), Math.round(w), Math.round(h), 128);
        },
    };

    return { ctx, image, drawn };
}
