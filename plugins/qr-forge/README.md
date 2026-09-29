# QR Forge

Turn any text or URL into a QR code you can download as PNG or SVG, with an
optional logo in the middle. Everything will happen in the browser — nothing
is uploaded, and the plugin has no backend logic, storage, config or
migrations. Built as a standalone tool plugin; see the repo root `CLAUDE.md`
for the overall monorepo shape.

## Status

**Skeleton.** The plugin boots, registers its card on the home page and
serves a placeholder page at `/qr-forge`. The generator itself is
**Planned** — see [`docs/PRD.md`](docs/PRD.md) for the spec and milestones.

## How it will work (Planned)

1. `GET /qr-forge` — public page with a text/URL input, a size selector and
   an optional logo picker.
2. The QR matrix is built in the browser with
   [qrcode-generator](https://github.com/kazuhikoarase/qrcode-generator),
   bundled by this plugin's own Vite build, and previewed live as you type.
3. PNG (via `<canvas>`) and SVG downloads are produced client-side. A logo is
   read with `FileReader`, never sent to the server, and forces error
   correction level H so the code still scans.

## Installation

The plugin is wired into `host/` through the path repository and a
`"techysavvy/qr-forge": "*"` entry in `host/composer.json`. From the repo root:

```bash
composer update techysavvy/qr-forge --working-dir=host
```

Once the front end lands (milestone 1), the plugin will also own a
`package.json` + `vite.config.js` like `photo-tweaker`, declare its bundle with
`AssetRegistry::register()` and request it with `@pluginAssets('qr-forge')`;
the root `Makefile`'s `install` target will need the matching
`npm install`/`npm run build --prefix plugins/qr-forge` lines.

## Routes

| Method | Path | Name |
|---|---|---|
| GET | `/qr-forge` | `qr-forge.home` |

## Configuration

None. There are no env vars or config keys.

## Layout

```
plugins/qr-forge/
├── composer.json
├── phpunit.xml
├── routes/web.php
├── resources/views/home.blade.php   # placeholder page
├── src/
│   ├── QrForge.php                  # ToolContract implementation
│   └── QrForgeServiceProvider.php
├── tests/
│   ├── TestCase.php                 # Testbench base
│   ├── Feature/QrForgeTest.php
│   └── Unit/
└── docs/PRD.md
```

## Tests

This plugin owns its tests in `tests/`, using `orchestra/testbench` to boot a
real Laravel app (they check registration in `ToolRegistry` and that the page
renders). It has its own `vendor/`, so it runs independently of `host/`:

```bash
composer install
vendor/bin/phpunit
```
