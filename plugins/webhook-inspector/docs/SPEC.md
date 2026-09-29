# Webhook Inspector — Spec

Status: Approved
Plugin: `plugins/webhook-inspector` · Date: 2026-09-30

## Problem & audience
Developers who are wiring up webhooks (Stripe, GitHub, Slack, their own services) need to see exactly what a sender posts: the method, headers, query and body. This is especially hard before their own endpoint exists or while it's failing. Webhook Inspector gives them a throwaway public URL to point the sender at, plus a live page that shows every request as it arrives. It covers both sides of the stack: a server-side catch-all capture endpoint and an interactive client-side viewer.

## Scope
**In:**
- One click creates a "bin": a public capture URL plus a separate secret viewer URL.
- The capture URL accepts any HTTP method, with an optional sub-path and query string, and stores the request.
- The viewer lists captured requests (newest first) and updates by polling every 2s.
- A request detail view shows the method, full path, query params, headers, and body (JSON pretty-printed, other text shown raw, binary summarized).
- The viewer has copy buttons for the capture URL and the request body.
- Bins expire 24h after creation and are pruned by a scheduled command. A bin can also be deleted early.

**Out (non-goals):**
- Custom responses (status, body, or delay chosen by the user). The sender always gets a fixed response.
- Replaying or forwarding a captured request to another URL.
- "Copy as cURL" export.
- Accounts, bin lists, or bin history across browsers.
- Server-Sent Events or websockets.
- Signature verification helpers (Stripe/GitHub HMAC).

## Core flow
1. The user opens `/webhook-inspector` and clicks **Create bin**.
2. The app redirects to the viewer URL `/webhook-inspector/b/{viewToken}`, which shows the capture URL `/webhook-inspector/in/{binId}` with a copy button and a "waiting for requests…" empty state.
3. The user pastes the capture URL into the sender, or `curl`s it.
4. The request appears in the list within about 2s. Clicking it shows the full detail.
5. After 24h (or on **Delete bin**), the capture URL returns 410 and the viewer shows an "expired" state.

## Requirements
- **R1** `POST /webhook-inspector/bins` creates a bin with a random 16-char lowercase-alphanumeric `binId` and a random 40-char `viewToken`, then redirects to the viewer.
- **R2** `ANY /webhook-inspector/in/{binId}/{path?}` (all of GET, POST, PUT, PATCH, DELETE, HEAD and OPTIONS, with `path` allowed to contain `/`) stores the method, sub-path, query string, headers, body, content type, body size, client IP and received-at time. It responds `200` with JSON `{"ok":true}`.
- **R3** The capture route runs outside the `web` middleware group: no session, no cookies set, no CSRF check. A POST with no token succeeds.
- **R4** A capture to an unknown bin returns `404`, and a capture to an expired or deleted bin returns `410`. Neither stores anything.
- **R5** A body larger than `max_body_kb` (default 256) is stored truncated to that size, with a `truncated` flag and the original size recorded.
- **R6** A body that isn't valid UTF-8 is stored base64-encoded with an `is_binary` flag. The viewer shows it as "binary, N bytes" rather than trying to render it.
- **R7** Each bin keeps at most `max_requests_per_bin` (default 200) requests. When a new capture goes over the limit, the oldest one is deleted.
- **R8** Captures are rate-limited to `capture_rate_per_minute` (default 60) per bin. Over the limit the endpoint returns `429` and stores nothing.
- **R9** `GET /webhook-inspector/b/{viewToken}/requests?after={id}` returns JSON for the requests with id > `after`, newest first, plus the bin's `expires_at`. An unknown token returns 404 and an expired one returns 410.
- **R10** The viewer polls R9 every 2s and prepends new requests without a full reload. Polling stops when the tab is hidden and resumes when it's visible again. Polling also stops on a 404 or 410 and shows the expired state.
- **R11** The detail view shows the method, full path with query, a query-param table, a header table, and the body. A JSON body (by content type, or because it parses as JSON) is pretty-printed. Other text is shown raw in a `<pre>`, escaped, never rendered as HTML.
- **R12** `DELETE /webhook-inspector/b/{viewToken}` deletes the bin and its requests immediately, then redirects to the home page.
- **R13** `webhook-inspector:prune` deletes expired bins and their requests. The plugin schedules it every `prune_interval_minutes` (default 5), the same way drop-share does.
- **R14** The capture URL (`binId`) alone never grants read access. Nothing in a capture response, and no other route keyed by `binId`, exposes captured data.
- **R15** Bin creation is throttled per IP (default 10/min) so bots can't mass-create bins.

## Data & privacy
- Server-side storage in the host DB: captured headers and bodies may contain secrets (auth headers, signing secrets, PII). They are kept for at most 24h, or until the bin is deleted or pruned.
- Captured bodies and headers are not written to the application log.
- The viewer URL is an unguessable bearer link. Anyone who has it can read the bin. The home page says this, along with "don't send production secrets".
- Nothing is sent to third parties.

## Dependencies
- Composer: none beyond `techysavvy/core` and `techysavvy/ui` (Laravel's built-in rate limiter and scheduler).
- npm: `vite` (dev only) for the plugin bundle. Viewer JS is plain JavaScript with no runtime dependencies.
- No PHP extensions, external services, or required env vars (all config has defaults).

## Routes & UI
| Route | Name | Middleware |
|---|---|---|
| `GET /webhook-inspector` | `webhook-inspector.home` | web |
| `POST /webhook-inspector/bins` | `webhook-inspector.bins.store` | web, throttle |
| `GET /webhook-inspector/b/{viewToken}` | `webhook-inspector.bins.show` | web |
| `GET /webhook-inspector/b/{viewToken}/requests` | `webhook-inspector.bins.requests` | web |
| `DELETE /webhook-inspector/b/{viewToken}` | `webhook-inspector.bins.destroy` | web |
| `ANY /webhook-inspector/in/{binId}/{path?}` | `webhook-inspector.capture` | throttle only (no `web`) |

- UI: `<x-ui::layout>`, `page-header`, `panel`, `button`, `input` (read-only capture URL), `alert` (privacy note and expired state), `tabs` (Headers / Query / Body in the detail view). Two-column list and detail layout in Tailwind using the theme tokens, stacked on mobile.
- Asset bundle: yes. `webhook-inspector.js` (polling, list rendering, JSON pretty-print, copy buttons) is registered with `AssetRegistry` and loaded with `@pluginAssets('webhook-inspector')`. Pure helpers (pretty-print, escaping, merge-new-requests) get `node --test` tests. The Makefile and CI gain the plugin's npm install, build, and test steps.
- Tool card: icon `🪝`, description "Get a throwaway URL and watch incoming webhooks arrive live."

## Persistence & config
- Migrations:
  - `webhook_inspector_bins`: `id`, `bin_id` (unique), `view_token` (unique), `expires_at` (indexed), timestamps.
  - `webhook_inspector_requests`: `id`, `bin_id` FK with cascade delete, `method`, `path`, `query` (text), `headers` (json), `body` (longText, nullable), `content_type`, `body_size`, `truncated`, `is_binary`, `ip`, `received_at`.
- `config/webhook-inspector.php`, with every key overridable through a `WEBHOOK_INSPECTOR_*` env var: `lifespan_hours` (24), `max_body_kb` (256), `max_requests_per_bin` (200), `capture_rate_per_minute` (60), `create_rate_per_minute` (10), `poll_interval_ms` (2000), `prune_interval_minutes` (5).

## Decisions
| Decision | Choice | Why | Source |
|---|---|---|---|
| Name | Webhook Inspector (`webhook-inspector`) | Literal, easy to find | user |
| Live updates | Poll every 2s for ids after the last seen one | No extra infra; works on php-fpm and `artisan serve` | user |
| Bin lifetime | 24h, configurable, scheduled prune | Matches drop-share | user |
| Access model | Separate secret viewer token; capture URL is write-only | Handing the capture URL to a third party doesn't expose captured secrets | user |
| Response to sender | Always `200 {"ok":true}` | Keeps v1 small; custom responses are a later feature | user |
| Body cap | 256 KB, truncated and flagged | Webhooks are small; bounds DB growth | user |
| Requests per bin | 200, oldest dropped | Bounds storage without breaking a long debug session | user |
| Capture rate limit | 60/min per bin → 429 | Stops a bin being used as a flood target | user |
| Bin creation limit | 10/min per IP | Anti-bot | user |
| Bin created by | Explicit POST button, not on page load | Crawlers hitting the home page don't create bins | user |
| Binary bodies | Stored base64 with a flag, shown as "binary, N bytes" | Safe storage and display; download is out of scope | user |
| Capture middleware | Outside `web` (no session/CSRF) | External senders have no CSRF token and need no cookies | user |
| Manual delete | "Delete bin" button in v1 | Lets users clear captured secrets right away | user |
| Frontend | Plain JS in a plugin Vite bundle, no framework | The job is small; matches the qr-forge bundle pattern | user |
| Viewer wiring | The bundle exposes an Alpine component (`window.webhookInspector`), like qr-forge | Alpine is already loaded by the `ui` layout, and `<x-ui::tabs>` needs it; no new dependency | user |
| Capture error bodies | 404 `{"error":"Bin not found."}`, 410 `{"error":"Bin expired."}`, 429 `{"error":"Too many requests."}` (JSON) | Senders and curl users get a readable reason, not an HTML error page | user |
| CORS on capture | Capture responses send `Access-Control-Allow-Origin: *`, `Access-Control-Allow-Methods: *` and `Access-Control-Allow-Headers: *`; OPTIONS is still captured | Lets browser-based senders (`fetch` from another site, including JSON/custom-header requests that preflight) reach the bin and read the response. Methods/Headers added at review. | user |
| Default selection | The newest request is auto-selected only while nothing is selected; new arrivals never steal an existing selection | Hands-free on first arrival without jumping away from what you're reading | user |
| Viewer header info | Capture URL with copy button, request count, and "Expires in Xh Ym" | Users know how long their bin lives | user |
| Delete bin | Native `confirm()` prompt ("Delete this bin and all captured requests?") before deleting | Irreversible action | user |
| Copy body | Copies the raw body as received (not the pretty-printed one); disabled for binary bodies | Exact payload for reuse in tests | user |
| Empty / truncated body | Shows "(empty body)"; a truncated body shows "Truncated: showing first 256 KB of N KB" above it | Makes the R5 flag visible | user |
| List row | Method badge, path (with query), relative time ("12s ago"), and body size | Scannable at a glance | user |

## Success criteria
- `curl -X POST -H 'Content-Type: application/json' -d '{"a":1}' <capture URL>` returns `{"ok":true}`, and the request shows up in an open viewer within about 3s with `{"a": 1}` pretty-printed.
- GET, PUT, DELETE, HEAD and OPTIONS with a sub-path and query string are all captured and shown correctly.
- A captured body of `<script>alert(1)</script>` is displayed as text and does not run.
- The capture URL alone can't be used to read anything. The viewer URL for another bin returns 404.
- After expiry, or after Delete, the capture returns 410 and the viewer shows the expired state.
- `webhook-inspector:prune` removes expired bins and their requests.
- The tool card appears on the host home page. PHP tests and `node --test` pass in CI.

## Open questions
None.
