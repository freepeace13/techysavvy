# Photo Tweaker

Crop, rotate, flip and resize an image, then export it as PNG, JPEG or WebP.
Everything happens in the browser on a `<canvas>` — nothing is uploaded and
the plugin has no backend logic, storage, config or migrations. Built as a
standalone tool plugin; see the repo root `CLAUDE.md` for the overall monorepo
shape.

## How it works

1. `GET /photo-tweaker` (`photo-tweaker.home`) renders a static Blade view;
   there are no other routes.
2. Choosing or dropping an image loads it into a canvas. Non-image files are
   rejected client-side with an inline error.
3. Crop (drag a box, then apply), rotate ±90°, flip horizontally/vertically
   and resize (with optional aspect-ratio lock) each redraw the canvas.
4. Export picks the format (JPEG/WebP expose a quality slider; JPEG is
   flattened onto white since it has no alpha) and downloads
   `photo-tweaker.<ext>` via a blob URL.

The Alpine component lives in `resources/js/photo-tweaker.js` and is exposed
as `window.photoTweaker`.

## Installation / assets

This plugin owns its front end: `resources/js/photo-tweaker.js` (which imports
`resources/css/photo-tweaker.css`) is built by the plugin's own Vite config, in
library mode, into `resources/dist/photo-tweaker.js` and `photo-tweaker.css`. `make install`
at the repo root runs the whole sequence; by hand, from the repo root:

```bash
npm install --prefix plugins/photo-tweaker
npm run build --prefix plugins/photo-tweaker     # -> resources/dist/photo-tweaker.{js,css}
```

Things worth knowing:

- **`resources/dist/` is generated and gitignored.** Never edit it; the
  sources are in `resources/js/` and `resources/css/`. Editing them needs only
  a rebuild — core serves `resources/dist/` directly, so there is no publish
  step. Until the build has run, the page's asset URLs 404.
- The script is a plain classic tag that sets `window.photoTweaker` for the page's
  `x-data`; it runs before the layout's deferred Alpine start.
- The plugin only *declares* the bundle (`AssetRegistry::register()` with an
  `AssetBundle` in its service provider) and requests it with
  `@pluginAssets('photo-tweaker')`; serving (`/_plugin-assets/photo-tweaker/…`, fingerprinted
  with `?v=`) and tag emission belong to `plugins/core`.
- `host/`'s own Vite build knows nothing about this plugin, and must not.

## Tests

This plugin owns its tests in `tests/`, using `orchestra/testbench` to boot a
real Laravel app (they check registration in `ToolRegistry` and that the page
renders). It has its own `vendor/`, so it runs independently of `host/`:

```bash
composer install
vendor/bin/phpunit
```
