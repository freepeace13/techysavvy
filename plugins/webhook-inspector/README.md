# Webhook Inspector

Get a throwaway URL, point any webhook sender at it, and watch each request
arrive live: method, path, query, headers and body. Built as a standalone
tool plugin; see the repo root `CLAUDE.md` for the overall monorepo shape.
Spec: `docs/SPEC.md`.

## How it works

1. `GET /webhook-inspector` is a public page with a **Create bin** button.
   Bins are only created by that POST (throttled per IP), never on page load.
2. A bin has two secrets: a 16-char `bin_id` in the **capture URL**
   (`/webhook-inspector/in/{binId}/{any/sub/path}`), and a 40-char
   `view_token` in the **viewer URL** (`/webhook-inspector/b/{viewToken}`).
   The capture URL is write-only, so handing it to Stripe or GitHub never
   exposes what was captured.
3. The capture route accepts any method, runs **outside** the `web` group
   (no session, cookies or CSRF), and stores the method, sub-path, raw query
   string, headers, body, content type, size, IP and time. It always answers
   `200 {"ok":true}`, with `404 {"error":"Bin not found."}`,
   `410 {"error":"Bin expired."}` or `429 {"error":"Too many requests."}` on
   failure. Every capture response sends `Access-Control-Allow-Origin/-Methods/-Headers: *`
   so browser senders, including preflighted ones, work.
4. Bodies over `max_body_kb` are truncated (on a UTF-8 boundary) and flagged.
   Non-UTF-8 bodies are stored base64 and shown as "binary, N bytes". Each bin
   keeps its newest `max_requests_per_bin` requests.
5. The viewer is an Alpine component (`resources/js/component.js`) that polls
   `GET /webhook-inspector/b/{viewToken}/requests?after={lastId}` every
   `poll_interval_ms`. It pauses while the tab is hidden, and stops and shows
   the expired state on 404/410. The newest request is auto-selected only when
   nothing is selected. Bodies are pretty-printed when they're JSON; all
   captured data is rendered with `x-text`, never as HTML.
6. **Delete bin** removes the requests at once and marks the bin expired, so
   the capture URL answers 410. `php artisan webhook-inspector:prune` deletes
   expired bins (requests cascade). The plugin schedules it every
   `prune_interval_minutes`, but **the host's scheduler must be running**
   (`php artisan schedule:work` in dev, or cron → `schedule:run`) for
   automatic cleanup to happen.

## Installation / assets

Wired into `host/` through the path repository and a
`"techysavvy/webhook-inspector": "*"` entry in `host/composer.json`. The plugin
ships a migration (two tables), so run it once:

```bash
composer update techysavvy/webhook-inspector --working-dir=host
cd host && php artisan migrate
```

It owns its JavaScript: the plugin's Vite config bundles
`resources/js/webhook-inspector.js` into a single IIFE,
`resources/dist/webhook-inspector.js`, which has no runtime dependencies
(Alpine comes from the `ui` layout). `make install` runs this; by hand:

```bash
npm install --prefix plugins/webhook-inspector
npm run build --prefix plugins/webhook-inspector
```

`resources/dist/` is generated and gitignored. The bundle is declared with
`AssetRegistry::register()` and requested only by the viewer page via
`@pluginAssets('webhook-inspector')`.

## Routes

| Method | Path | Name |
|---|---|---|
| GET | `/webhook-inspector` | `webhook-inspector.home` |
| POST | `/webhook-inspector/bins` | `webhook-inspector.bins.store` |
| GET | `/webhook-inspector/b/{viewToken}` | `webhook-inspector.bins.show` |
| GET | `/webhook-inspector/b/{viewToken}/requests` | `webhook-inspector.bins.requests` |
| DELETE | `/webhook-inspector/b/{viewToken}` | `webhook-inspector.bins.destroy` |
| ANY | `/webhook-inspector/in/{binId}/{path?}` | `webhook-inspector.capture` (not in `web`) |

## Configuration

`config/webhook-inspector.php`, each key overridable by env:

| Key | Env | Default |
|---|---|---|
| `lifespan_hours` | `WEBHOOK_INSPECTOR_LIFESPAN_HOURS` | 24 |
| `max_body_kb` | `WEBHOOK_INSPECTOR_MAX_BODY_KB` | 256 |
| `max_requests_per_bin` | `WEBHOOK_INSPECTOR_MAX_REQUESTS_PER_BIN` | 200 |
| `capture_rate_per_minute` | `WEBHOOK_INSPECTOR_CAPTURE_RATE_PER_MINUTE` | 60 |
| `create_rate_per_minute` | `WEBHOOK_INSPECTOR_CREATE_RATE_PER_MINUTE` | 10 |
| `poll_interval_ms` | `WEBHOOK_INSPECTOR_POLL_INTERVAL_MS` | 2000 |
| `prune_interval_minutes` | `WEBHOOK_INSPECTOR_PRUNE_INTERVAL_MINUTES` | 5 |

## Privacy

Captured headers and bodies can contain secrets. They live in the host
database until the bin expires or is deleted, and are never logged. Anyone
with a viewer link can read that bin; the home page says so.

## Planned (not built)

Custom responses (status/body/delay), replay/forward, copy as cURL, accounts
or bin history, SSE/websockets, and signature-verification helpers. These are
the spec's non-goals.

## Layout

```
plugins/webhook-inspector/
├── composer.json / package.json / vite.config.js
├── config/webhook-inspector.php
├── database/migrations/          # bins + requests tables
├── routes/web.php
├── resources/
│   ├── js/format.js              # pure display helpers
│   ├── js/component.js           # Alpine viewer component
│   ├── js/webhook-inspector.js   # bundle entry, sets window.webhookInspector
│   └── views/{home,show}.blade.php
├── src/
│   ├── Services/BinService.php   # create / capture / delete / prune
│   ├── Http/Controllers/         # BinController, CaptureController
│   ├── Models/                   # Bin, CapturedRequest
│   └── Console/PruneExpiredBinsCommand.php
├── tests/Feature/                # Testbench
├── tests/js/                     # node --test
└── docs/SPEC.md, docs/plans/
```

## Tests

```bash
composer install && vendor/bin/phpunit   # PHP, via orchestra/testbench
npm run build && npm test                # JS; the bundle test needs the build
```
