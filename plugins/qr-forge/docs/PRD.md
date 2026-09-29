# QR Forge — PRD

## Problem & audience

People regularly need a QR code for a link or a snippet of text — a flyer, a
slide, a printed label, sharing a Drop Share phrase. Most online generators
are ad-heavy, track what you encode, or gate SVG/logo export behind a paywall.
QR Forge is a quick, private, free alternative inside techysavvy.

## Goals

- Generate a scannable QR code from arbitrary text or a URL, live as you type.
- Download it as PNG (chosen pixel size) or SVG (vector).
- Optionally overlay a center logo while keeping the code scannable.
- Run entirely client-side: nothing is uploaded, stored or logged.

## Non-goals (v1)

- Wi-Fi, vCard, email or other structured presets.
- Custom foreground/background colors or module styles.
- A user-facing error-correction selector.
- Scanning/decoding QR codes.
- History, saved codes, short links or analytics.
- Any server-side endpoint beyond the page itself.

## Core flow

1. User opens `/qr-forge`.
2. Types or pastes text / a URL. The preview updates live (debounced).
3. Optionally picks an output size and uploads a logo image.
4. Clicks **Download PNG** or **Download SVG**.

## Functional requirements

1. **Input** — a single textarea. Empty input shows an empty state, not a
   broken code. Input longer than the QR capacity for the current error level
   shows a clear message instead of throwing.
2. **Encoding** — `qrcode-generator` with type number `0` (auto) and UTF-8
   byte mode so non-ASCII text encodes correctly.
3. **Error correction** — `M` by default; `H` automatically whenever a logo
   is present.
4. **Size** — preset PNG sizes (e.g. 256 / 512 / 1024 px) with a quiet zone of
   4 modules. SVG is resolution-independent and uses a `viewBox`.
5. **Logo** — accepts `image/*`, read via `FileReader` (never uploaded),
   drawn centered at ≤ ~20% of the code's width on a white padded backing, in
   both the PNG and the SVG (embedded as a data URI). A "remove logo" control
   restores level `M`.
6. **Export** — PNG from `<canvas>.toBlob()`, SVG from a generated string;
   both downloaded with a filename like `qr-forge.png` / `qr-forge.svg`.
7. **UI** — built from `<x-ui::...>` components (`page-header`, `panel`,
   `input`, `button`, `dropzone` for the logo, `alert` for errors), with Alpine
   state like `photo-tweaker`.

## Data & privacy

Fully client-side. The text and logo never leave the browser; there are no
requests besides loading the page and its asset bundle. No storage, cookies
or third-party calls.

## Dependencies

- npm: `qrcode-generator` (MIT, no dependencies) and `vite` (dev), bundled
  in library/IIFE mode into `resources/dist/qr-forge.js` (+ `.css` if needed).
- No Composer dependencies beyond `techysavvy/core` and `techysavvy/ui`.
- No PHP extensions, env vars or API keys.

## Routes / UI outline

| Method | Path | Name | Purpose |
|---|---|---|---|
| GET | `/qr-forge` | `qr-forge.home` | The whole tool |

Layout: input panel (text, size, logo) beside a preview panel (live code +
two download buttons); stacks vertically on narrow screens.

## Open questions

- Exact PNG size presets, or a free-form number input with bounds?
- Should the logo size be adjustable, or fixed at a safe ratio?

## Success criteria

- A downloaded PNG and SVG each decode (phone camera or `zbarimg`) to exactly
  the input text, including non-ASCII text and with a logo overlaid.
- No network request is made when generating or downloading.
- Plugin tests pass; the host `ToolListingTest` passes unchanged.

## Milestones

1. **Front-end scaffold** — add `package.json` (`qrcode-generator`, `vite`),
   `vite.config.js` (copy `photo-tweaker`'s), `resources/js/qr-forge.js`;
   register the bundle via `AssetRegistry` in the service provider, request it
   with `@pluginAssets('qr-forge')`, add the build to the root `Makefile`
   `install` target. Add feature tests for bundle requests and "no inline
   script/style" (mirroring `PhotoTweakerTest`).
2. **Pure QR module** — a small ES module that turns `(text, ecLevel)` into
   a module matrix, and matrix → SVG string; unit-test it with `node --test`.
3. **Live preview + text input** — Alpine component, debounced rendering,
   empty and over-capacity states.
4. **PNG & SVG export** — canvas renderer with size presets; downloads.
5. **Center logo** — dropzone, `FileReader`, auto level H, overlay in both
   renderers, remove control.
6. **Polish & verify** — responsive layout, scan-test the outputs, update the
   README status from Skeleton to done.
