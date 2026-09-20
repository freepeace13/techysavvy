# EnvDiff

Compare two `.env` files, spot drift and leaked secrets, and generate a clean
`.env.example` — entirely in the browser. Built as a standalone tool plugin;
see the repo root `CLAUDE.md` for the overall monorepo shape.

## Status

**Skeleton.** The plugin boots, registers its home-page card, and serves a
placeholder page carrying the "why it is safe to paste your env here" note.
The diff, secret detection, and example generation are **Planned** — see
[`docs/PRD.md`](docs/PRD.md).

## Privacy model (Planned behavior)

The page's safety note promises that:

- input is parsed by JavaScript in the visitor's browser and never sent to the server;
- nothing is stored, logged, or written to `localStorage`;
- the page loads no third-party scripts, analytics, or trackers;
- the visitor can verify this in the browser's Network tab.

These claims are only true once the feature is built client-side, so any
implementation must uphold them (no `fetch`/form POST of the input, no
third-party assets on this page).

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

## Configuration and dependencies

None. No config keys, env vars, migrations, or extra packages.

## Layout

```
composer.json
src/EnvDiff.php                     ToolContract implementation
src/EnvDiffServiceProvider.php      routes, views, ToolRegistry registration
routes/web.php
resources/views/home.blade.php      placeholder page + safety note
docs/PRD.md
```

## Tests

No plugin-level tests yet (no logic). The host's registry-driven
`ToolListingTest` covers the card and route. Add plugin tests when the
diff/detection logic lands.
