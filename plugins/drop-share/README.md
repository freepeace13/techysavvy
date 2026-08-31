# Drop Share

Upload a file, get a random phrase, share the phrase — anyone with it can
download the file until it expires. Built as a standalone tool plugin; see
the repo root `CLAUDE.md` for the overall monorepo shape.

## How it works

1. `GET /drop-share` — public page with two forms: upload, and retrieval.
2. Uploading a file (`POST /drop-share/upload`) encrypts its contents
   (AES-256-CBC via Laravel's `Crypt` facade, keyed off `APP_KEY`) and
   stores them on the configured disk, generates a unique 4-word phrase
   (via `drenso/genphrase`), and shows the phrase on the page.
3. Retrieving a file (`POST /drop-share/download`) with a valid,
   non-expired phrase decrypts and streams the original file back.
4. `php artisan drop-share:prune` deletes expired rows and their files. The
   plugin schedules this itself (no host file edits) — **the host's
   scheduler must actually be running** (`php artisan schedule:work` in
   dev, or a cron entry hitting `php artisan schedule:run` every minute in
   production) for automatic cleanup to happen. Expired files are also
   always treated as not-found on read, even if the schedule hasn't run
   yet, so a slow scheduler never serves an expired file.

## Configuration

All settings live in `config/drop-share.php` and are env-overridable —
nothing storage/size/lifespan-related is hardcoded in `src/`:

| Env var | Config key | Default | Meaning |
|---|---|---|---|
| `DROP_SHARE_DISK` | `disk` | `local` | Any disk name defined in `filesystems.php` (`local`, `public`, `s3`, ...). |
| `DROP_SHARE_MAX_UPLOAD_KB` | `max_upload_kb` | `25600` (25MB) | Max upload size in kilobytes. |
| `DROP_SHARE_LIFESPAN_HOURS` | `lifespan_hours` | `24` | Hours an upload lives before it's eligible for deletion. |
| `DROP_SHARE_PRUNE_INTERVAL_MINUTES` | `prune_interval_minutes` | `5` | How often the scheduled prune runs. |

## Security notes / caveats

- File bytes are encrypted at rest; plaintext only exists transiently in
  memory during upload processing and download response generation.
- Confidentiality is tied to the host's shared `APP_KEY` — rotating it
  invalidates any not-yet-downloaded uploads. Acceptable given the default
  24h lifespan; worth reconsidering if this pattern is reused for something
  more sensitive.
- Both forms are rate-limited per IP (`5` uploads/min, `10` downloads/min)
  to blunt scripted abuse and phrase brute-forcing. 4-word GenPhrase
  entropy makes phrase guessing impractical even without the limiter.
- **No file-type restriction and no malware/virus scanning** — any file
  type is accepted, size-limited only. This is an explicit, intentional
  gap, not an oversight.
- No per-file or total-storage quota — if abuse outpaces the rate limit and
  default 24h lifespan, that's a follow-up, not handled today.
- Upload size is also bound by PHP's `upload_max_filesize`/`post_max_size`
  ini settings, which this plugin cannot enforce or override — make sure
  they're set at least as high as `DROP_SHARE_MAX_UPLOAD_KB`.
- This plugin's `routes/web.php` explicitly wraps its routes in Laravel's
  `web` middleware group. Routes registered via a plugin `ServiceProvider`'s
  `loadRoutesFrom()` do **not** inherit that group automatically — only
  routes declared directly in `host/routes/web.php` do, per
  `bootstrap/app.php`'s `withRouting()`. Without this, session (flashed
  phrase/error), CSRF protection, and validation error sharing (`$errors`)
  would silently not work. Any future plugin with forms or session state
  needs the same explicit wrap.

## Tests

This plugin owns its tests in `tests/`, using `orchestra/testbench` (its
own `require-dev` dependency — not a repo-wide convention) to boot a real
Laravel app for HTTP/DB-level coverage, plus plain PHPUnit for pure logic
(`PhraseGenerator`). It has its own self-contained `vendor/` (via its own
`repositories` path entries to sibling plugins) so it can run independently
of `host/`. Install and run from this directory:

```bash
composer install
vendor/bin/phpunit
```
