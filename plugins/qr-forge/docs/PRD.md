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
4. **Size** — preset PNG sizes (256 / 512 / 1024 / 2048 px) with a quiet zone of
   4 modules. SVG is resolution-independent and uses a `viewBox`.
5. **Logo** — accepts `image/*`, read via `FileReader` (never uploaded),
   drawn centered on a white margin covering ≤ 30% of the code's width, in
   both the PNG and the SVG (embedded as a data URI). Short codes are bumped
   to version 5 so the logo has room. A "remove logo" control restores
   level `M`.
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

## Decisions

- PNG sizes are presets (256–2048 px), not free-form input.
- Logo size is fixed at a safe ratio (≤ 30% of the width at level H), not
  user-adjustable.
- `jsqr` is a dev dependency only: tests decode rendered output to prove the
  codes scan.

## Success criteria

- A downloaded PNG and SVG each decode (phone camera or `zbarimg`) to exactly
  the input text, including non-ASCII text and with a logo overlaid.
- No network request is made when generating or downloading.
- Plugin tests pass; the host `ToolListingTest` passes unchanged.

## Milestones

All v1 milestones are done:

1. ~~Front-end scaffold~~ — Vite bundle registered via `AssetRegistry`,
   `make install` and CI build/test it.
2. ~~Pure QR module~~ — `resources/js/qr.js`, tested by decoding output.
3. ~~Live preview + text input~~ — `resources/js/component.js`.
4. ~~PNG & SVG export~~.
5. ~~Center logo~~.
6. ~~Polish & verify~~ — decoded real Chromium downloads (PNG + SVG, with and
   without a logo, UTF-8 input).

Backlog (out of v1 scope): Wi-Fi/vCard presets, custom colors, manual
error-correction choice.
