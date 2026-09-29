# QR Forge

Turn any text or URL into a QR code you can download as PNG or SVG, with an
optional logo in the middle. Everything happens in the browser — nothing is
uploaded, and the plugin has no backend logic, storage, config or migrations.
Built as a standalone tool plugin; see the repo root `CLAUDE.md` for the
overall monorepo shape.

## How it works

1. `GET /qr-forge` — public page with a text/URL box, an optional logo
   dropzone, a live preview and PNG/SVG download buttons.
2. `resources/js/qr.js` builds the code with
   [qrcode-generator](https://github.com/kazuhikoarase/qrcode-generator) and
   renders it two ways: `toSvg()` (the preview and the SVG download) and
   `drawToCanvas()` (the PNG download, at 256–2048 px). Both add the
   standard 4-module quiet zone.
3. A logo is read with `FileReader` and never leaves the browser. It switches
   error correction from M to H, bumps short codes to at least version 5 so
   the logo has room, and sits on a white margin covering at most 30% of the
   code's width (`logoBox()`), which H recovers from.
4. `resources/js/component.js` is the page's Alpine component
   (`x-data="qrForge()"`): debounced re-render on input, logo validation and
   the downloads (`qr-forge.png` / `qr-forge.svg`).

## Installation / assets

The plugin is wired into `host/` through the path repository and a
`"techysavvy/qr-forge": "*"` entry in `host/composer.json`:

```bash
composer update techysavvy/qr-forge --working-dir=host
```

It owns its JavaScript: `package.json` declares `qrcode-generator`, and the
plugin's Vite config bundles `resources/js/qr-forge.js` in library mode into a
single IIFE, `resources/dist/qr-forge.js`. `make install` at the repo root runs
the whole sequence; by hand, from the repo root:

```bash
npm install --prefix plugins/qr-forge
npm run build --prefix plugins/qr-forge     # -> resources/dist/qr-forge.js
```

- **`resources/dist/` is generated and gitignored.** Never edit it; editing
  `resources/js/` needs only a rebuild — core serves `resources/dist/`
  directly. Until the build has run, the page's script URL 404s.
- The plugin only *declares* the bundle (`AssetRegistry::register()` in its
  service provider) and requests it with `@pluginAssets('qr-forge')`; serving
  and tag emission belong to `plugins/core`. `host/`'s Vite build knows
  nothing about this plugin.

## Routes

| Method | Path | Name |
|---|---|---|
| GET | `/qr-forge` | `qr-forge.home` |

## Configuration

None. There are no env vars or config keys.

## Caveats

- qrcode-generator only encodes Latin-1 in byte mode, so `qr.js` UTF-8-encodes
  the text first. Modern scanners read it as UTF-8; there is no ECI header.
- Long text makes a dense code that is hard to scan when printed small. The
  capacity limit (1,273 UTF-8 bytes with a logo, 2,331 without) shows an error.
- An SVG logo is embedded as a data URI inside `<image>`, where browsers
  don't run scripts; it is escaped before going into the attribute.

## Layout

```
plugins/qr-forge/
├── composer.json / package.json / vite.config.js
├── routes/web.php
├── resources/
│   ├── js/qr.js            # encode + SVG/canvas renderers (pure)
│   ├── js/component.js     # Alpine component
│   ├── js/qr-forge.js      # bundle entry, sets window.qrForge
│   └── views/home.blade.php
├── src/                    # ToolContract + ServiceProvider
├── tests/Feature/          # Testbench: registration, page, bundle
├── tests/js/               # node --test: encoding, rendering, component, bundle
└── docs/PRD.md
```

## Tests

PHP tests use `orchestra/testbench` to boot a real Laravel app; the plugin has
its own `vendor/`, so it runs independently of `host/`:

```bash
composer install
vendor/bin/phpunit
```

JS tests decode what the renderers actually drew with
[jsQR](https://github.com/cozmo/jsQR), so they prove the codes scan —
including with a logo overlaid — rather than asserting on markup. The bundle
test needs `npm run build` first:

```bash
npm run build
npm test
```
