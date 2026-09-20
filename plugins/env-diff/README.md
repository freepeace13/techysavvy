# EnvDiff

Compare two `.env` files, spot drift and leaked secrets, and generate a clean
`.env.example` — entirely in the browser. Built as a standalone tool plugin;
see the repo root `CLAUDE.md` for the overall monorepo shape.

## Status

**v1 implemented.** Paste two `.env` files at `/env-diff` to get:

- missing and extra keys between them;
- empty and placeholder values (`changeme`, `xxx`, `your-…`, `<…>`);
- likely real secrets (known key formats, high-entropy values, sensitive key
  names), reported with a masked preview only;
- duplicate keys and lines that aren't `KEY=value`;
- a copyable `.env.example` generated from file A (values stripped, comments
  and order kept).

See [`docs/PRD.md`](docs/PRD.md) for scope and non-goals.

## Privacy model

The page's "why it is safe to paste your env here" note promises that:

- input is parsed by JavaScript in the visitor's browser and never sent to the server;
- nothing is stored, logged, or written to `localStorage`;
- the page loads no third-party scripts, analytics, or trackers;
- the visitor can verify this in the browser's Network tab.

How the code upholds this: the logic is plain JS in `resources/js/`, the page
has no form and no server route that accepts input (`POST /env-diff` is a 405),
and tests enforce it — the built bundle is scanned for `fetch`, XHR, beacons,
WebSockets and browser storage APIs, and the rendered page is checked for
inline scripts and third-party assets. Any change must keep those true.

## Installation / wiring

The plugin is wired into `host/` through `host/composer.json`
(`"techysavvy/env-diff": "*"`) and the monorepo path repository. From the
repo root:

```bash
composer update techysavvy/env-diff --working-dir=host
```

## Routes

| Method | Path | Name |
|---|---|---|
| GET | `/env-diff` | `env-diff.home` |

## Assets

The plugin owns its JS build (`package.json` + `vite.config.js`, library mode,
IIFE, no runtime dependencies). `make install` builds it; by hand from the repo
root:

```bash
npm install --prefix plugins/env-diff
npm run build --prefix plugins/env-diff     # -> resources/dist/env-diff.js (gitignored)
```

The service provider declares the bundle with `AssetRegistry::register()` and
the page requests it with `@pluginAssets('env-diff')`; core serves it.

## Configuration and dependencies

No config keys, env vars, migrations, or runtime packages.

## Layout

```
composer.json
src/EnvDiff.php                     ToolContract implementation
src/EnvDiffServiceProvider.php      routes, views, ToolRegistry registration
routes/web.php
resources/views/home.blade.php      page, results UI, safety note
resources/js/parse.js               dotenv parser
resources/js/analyze.js             diff, placeholder and secret heuristics
resources/js/example.js             .env.example generator
resources/js/env-diff.js            Alpine component (window.envDiff)
tests/js/                           node:test suites (logic + built bundle)
tests/Feature/                      Testbench page tests
docs/PRD.md
```

## Tests

```bash
npm run build && npm test          # JS logic + built-bundle checks
composer install && vendor/bin/phpunit   # page tests (Testbench)
```

The host's registry-driven `ToolListingTest` covers the home-page card.

## Heuristic limits

Secret detection is deliberately conservative and labelled "likely": it can
miss unusual secrets and can flag benign high-entropy values. It is a
sanity check, not a scanner — don't rely on it to certify a file is clean.
