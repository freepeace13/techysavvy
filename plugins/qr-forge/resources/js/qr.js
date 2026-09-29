import qrcode from 'qrcode-generator';

// Blank border, in modules, that scanners need around the code (per the spec).
export const QUIET_ZONE = 4;

// Smallest version used under a logo: below it the logo box is a module or two wide.
const MIN_LOGO_VERSION = 5;

export class QrTooLongError extends Error {
    constructor() {
        super('That text is too long to fit in a QR code. Shorten it and try again.');
        this.name = 'QrTooLongError';
    }
}

// qrcode-generator's byte mode keeps only the low byte of each UTF-16 code
// unit, so hand it the UTF-8 bytes as a "binary" string of 0-255 char codes.
function utf8BinaryString(text) {
    return Array.from(new TextEncoder().encode(text), (byte) => String.fromCharCode(byte)).join('');
}

function make(data, version, level) {
    const code = qrcode(version, level);
    code.addData(data, 'Byte');

    try {
        code.make();
    } catch (error) {
        if (String(error).includes('overflow')) throw new QrTooLongError();
        throw error;
    }

    return code;
}

/**
 * Error correction is H when a logo will cover the middle of the code, so the
 * modules it hides can be recovered; M otherwise.
 */
export function createQr(text, { withLogo = false } = {}) {
    const level = withLogo ? 'H' : 'M';
    const data = utf8BinaryString(text);

    let code = make(data, 0, level);
    if (withLogo && (code.getModuleCount() - 17) / 4 < MIN_LOGO_VERSION) {
        code = make(data, MIN_LOGO_VERSION, level);
    }

    return {
        size: code.getModuleCount(),
        level,
        isDark: (row, col) => code.isDark(row, col),
    };
}

/**
 * Where the logo sits, in module units of the full image (quiet zone
 * included): a square of `size` inside a white margin of `pad`. The whole box
 * covers at most 30% of the code's width (~9% of its area), inside what
 * level H recovers, and has the code's parity so it stays grid-aligned.
 */
export function logoBox(size) {
    let outer = Math.floor(size * 0.3);
    if (outer % 2 !== size % 2) outer -= 1;

    const pad = 1;
    const start = QUIET_ZONE + (size - outer) / 2;

    return { x: start + pad, y: start + pad, size: outer - 2 * pad, pad };
}

function escapeAttribute(value) {
    return value.replace(/[&<>"']/g, (c) => `&#${c.charCodeAt(0)};`);
}

export function toSvg(qr, { logoDataUrl = null } = {}) {
    const total = qr.size + 2 * QUIET_ZONE;
    let path = '';

    for (let row = 0; row < qr.size; row++) {
        for (let col = 0; col < qr.size; col++) {
            if (qr.isDark(row, col)) path += `M${col + QUIET_ZONE} ${row + QUIET_ZONE}h1v1h-1z`;
        }
    }

    let logo = '';
    if (logoDataUrl) {
        const box = logoBox(qr.size);
        const outer = box.size + 2 * box.pad;
        logo = `<rect x="${box.x - box.pad}" y="${box.y - box.pad}" width="${outer}" height="${outer}" fill="#ffffff"/>`
            + `<image x="${box.x}" y="${box.y}" width="${box.size}" height="${box.size}" preserveAspectRatio="xMidYMid meet" href="${escapeAttribute(logoDataUrl)}"/>`;
    }

    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${total} ${total}" shape-rendering="crispEdges">`
        + `<rect width="${total}" height="${total}" fill="#ffffff"/>`
        + `<path fill="#000000" d="${path}"/>`
        + logo
        + '</svg>';
}

/**
 * Draws onto a px × px canvas. Module edges are rounded to whole pixels so
 * any px works without anti-aliased seams between modules. `logo` is anything
 * drawImage accepts that has a width and height (an HTMLImageElement).
 */
export function drawToCanvas(ctx, qr, px, logo = null) {
    const total = qr.size + 2 * QUIET_ZONE;
    const edge = (module) => Math.round((module * px) / total);
    const fillModules = (x, y, w, h) => ctx.fillRect(edge(x), edge(y), edge(x + w) - edge(x), edge(y + h) - edge(y));

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, px, px);

    ctx.fillStyle = '#000000';
    for (let row = 0; row < qr.size; row++) {
        for (let col = 0; col < qr.size; col++) {
            if (qr.isDark(row, col)) fillModules(col + QUIET_ZONE, row + QUIET_ZONE, 1, 1);
        }
    }

    if (!logo) return;

    const box = logoBox(qr.size);
    ctx.fillStyle = '#ffffff';
    fillModules(box.x - box.pad, box.y - box.pad, box.size + 2 * box.pad, box.size + 2 * box.pad);

    const side = (box.size * px) / total;
    const scale = Math.min(side / logo.width, side / logo.height);
    const w = logo.width * scale;
    const h = logo.height * scale;
    const x = (box.x * px) / total + (side - w) / 2;
    const y = (box.y * px) / total + (side - h) / 2;

    ctx.imageSmoothingEnabled = true;
    ctx.drawImage(logo, x, y, w, h);
}
