# Drop Share Tool — Design

## Problem Statement

We want a public-facing tool, `plugins/drop-share`, that lets an anonymous
visitor upload any file (under a size limit), receive a unique random
passphrase for it, and lets anyone with that phrase download the file again.
Entries expire automatically (default 24h, configurable) and are purged —
both the database row and the file on disk — after expiry. Because this is a
public, unauthenticated, write-accepting endpoint, it needs rate limiting and
encryption-at-rest to be safe to expose. Storage driver, upload size limit,
and file lifespan are all config-driven through the plugin's own config file
— no code change needed to retune any of them.

## Scope

In scope: the `drop-share` plugin (upload, phrase generation, retrieval,
expiry/cleanup, rate limiting, at-rest encryption), plugin-owned tests, and
plugin-owned docs (README).

Out of scope: virus/malware scanning, file-type restriction (any file type is
allowed, size-limited only), authentication/ownership of uploads, admin UI to
browse/manage uploads.

## Naming

Scaffolded via the `create-plugin` skill's naming convention. Chosen name:
**Drop Share**.

| Use | Format | Value |
|---|---|---|
| `plugins/` dir, composer suffix, route prefix, view namespace | kebab-case | `drop-share` |
| PHP namespace segment, class prefix | StudlyCase | `DropShare` |
| Display `name()` | Title Case | `Drop Share` |
| `icon()` | emoji | `📦` |
| `description()` | one sentence | `Upload a file, get a phrase, share it — auto-deletes after expiry.` |

## Architecture

Standard plugin shape (matches `plugins/hello-tool`, built via the
`create-plugin` skill's recipe), added as `plugins/drop-share/`, Composer
package `techysavvy/drop-share`, requiring `techysavvy/core`,
`techysavvy/ui`, and `genphrase/genphrase`.

```
plugins/drop-share/
├── composer.json
├── src/
│   ├── DropShareServiceProvider.php
│   ├── DropShareTool.php                 # implements ToolContract
│   ├── Models/SharedFile.php
│   ├── Services/PhraseGenerator.php      # wraps GenPhrase
│   ├── Services/DropShareService.php     # store/retrieve/prune logic, encryption
│   ├── Http/Controllers/UploadController.php
│   ├── Http/Controllers/DownloadController.php
│   ├── Http/Requests/StoreUploadRequest.php
│   └── Console/PruneExpiredFilesCommand.php
├── routes/web.php
├── resources/views/home.blade.php
├── config/drop-share.php
├── database/migrations/xxxx_create_drop_share_uploads_table.php
├── phpunit.xml                            # testbench-based, plugin-local
└── tests/
    ├── TestCase.php                       # Orchestra\Testbench\TestCase subclass
    └── Feature/{UploadTest,DownloadTest,PruneExpiredFilesTest,ToolRegistrationTest}.php
```

`DropShareServiceProvider::boot()` wires everything at runtime — routes,
views (`drop-share::` namespace), config, migrations, named rate limiters,
the scheduled prune command, and registration into `ToolRegistry`. Nothing
in `host/` is edited except adding the package to `host/composer.json`'s
`require` block, per the `create-plugin` recipe.

## Data Model

Single table `drop_share_uploads`:

| column | type | notes |
|---|---|---|
| id | bigint pk | |
| phrase | string, unique, indexed | GenPhrase output, e.g. `correct-horse-battery-staple` |
| disk_path | string | random UUID-based path on the configured disk; holds **encrypted** bytes |
| original_name | string | restored as the download filename |
| mime_type | string | used for the download `Content-Type` header |
| size | unsigned integer | original (pre-encryption) byte size, for display/validation |
| expires_at | timestamp, indexed | `now() + config('drop-share.lifespan_hours')` at upload time |
| timestamps | | |

`SharedFile` is a plain Eloquent model, `$fillable` on the columns above, no
relationships. `disk_path` is intentionally decoupled from `phrase` (a
directory listing must never reveal a phrase).

## Storage & Encryption — fully config-driven

No disk is registered or hardcoded by the plugin. Instead,
`config('drop-share.disk')` names any disk already defined in Laravel's
`filesystems.disks` config (`local`, `public`, `s3`, or a custom one the
host operator adds) — the plugin is agnostic to which driver backs it.
Files are written under a `drop-share/` path prefix on that disk so they
don't collide with anything else stored there. Swapping storage driver
(e.g. local → S3) is therefore a one-line env change
(`DROP_SHARE_DISK=s3`), no code or plugin-config-schema change.

- Default disk is `local` (`storage/app/private` — not web-accessible,
  matches host's existing `filesystems.php` default). Operators wanting a
  public-bucket-style disk must still route through this plugin's
  phrase-gated download route to actually retrieve a file; the disk being
  "public" doesn't expose file contents because the encrypted blob at
  `disk_path` is meaningless without decryption, and `disk_path` values are
  never surfaced anywhere.
- On upload, `DropShareService` reads the uploaded file's contents and
  encrypts them with Laravel's `Crypt` facade (`Crypt::encryptString`,
  AES-256-CBC, keyed off `APP_KEY`) before writing the ciphertext to the
  configured disk. Plaintext is never persisted.
- On a valid, non-expired download request, the ciphertext is read back,
  decrypted in-memory (`Crypt::decryptString`), and returned as a download
  response (`Content-Disposition: attachment`, original filename/mime from
  the DB row). Decrypted bytes are never written to disk.
- Whole-file in-memory encrypt/decrypt is acceptable given the default 25MB
  cap (`max_upload_kb`, itself config-driven).
- This ties confidentiality to the host's `APP_KEY`; documented as a caveat
  in the plugin README (rotating `APP_KEY` invalidates in-flight uploads —
  acceptable given the 24h default lifespan).

## Phrase Generation

`PhraseGenerator` wraps `GenPhrase\Generator` configured for 4 words,
hyphen-separated (e.g. `correct-horse-battery-staple`). On the rare event of
a phrase collision against an existing non-expired row, regenerate and retry
(bounded retries, e.g. 5, then throw — surfaced as a 500 so it's visible
rather than silently colliding).

## Config (`config/drop-share.php`, fully env-overridable)

Everything storage-, size-, and lifespan-related lives here — nothing is
hardcoded in `src/`.

```php
return [
    // Any disk name defined in filesystems.php ('local', 'public', 's3', ...).
    'disk' => env('DROP_SHARE_DISK', 'local'),

    // Max upload size, in kilobytes.
    'max_upload_kb' => env('DROP_SHARE_MAX_UPLOAD_KB', 25600), // 25MB

    // How long an uploaded file lives before it's eligible for deletion.
    'lifespan_hours' => env('DROP_SHARE_LIFESPAN_HOURS', 24),

    // How often the prune command runs, when scheduled by this plugin.
    'prune_interval_minutes' => env('DROP_SHARE_PRUNE_INTERVAL_MINUTES', 5),
];
```

## Routes & Controllers

- `GET /drop-share` — home page, two-column grid layout (upload form |
  retrieval form), rendered by `resources/views/home.blade.php` using
  `<x-brand::layout>`.
- `POST /drop-share/upload` — `UploadController@store`. Validated by
  `StoreUploadRequest` (`file`, `max:<max_upload_kb>` pulled from config at
  validation time, not hardcoded in rule strings). On success: encrypts and
  stores the file on `config('drop-share.disk')`, creates the `SharedFile`
  row with `expires_at` computed from `config('drop-share.lifespan_hours')`,
  redirects back to `GET /drop-share` with the phrase flashed into the
  session and rendered in the upload column (no JS/API needed — plain form
  POST + redirect). Throttled via a named rate limiter, `5`
  requests/minute per IP.
- `POST /drop-share/download` — `DownloadController@store`. Takes `phrase`.
  - Not found or expired → flashed error, redisplay the retrieval form (no
    information disclosure about *why* — same message either way).
  - Found and valid → decrypt and stream the file as an attachment.
  - Throttled via a named rate limiter, `10` requests/minute per IP.

Rate limiters are defined in `DropShareServiceProvider::boot()` via
`RateLimiter::for('drop-share-uploads', ...)` /
`RateLimiter::for('drop-share-downloads', ...)`, keyed by `Request::ip()`,
and applied to the routes via `throttle:drop-share-uploads` /
`throttle:drop-share-downloads` middleware.

## Expiry & Cleanup

- `php artisan drop-share:prune` (plugin-owned Artisan command): finds all
  `drop_share_uploads` rows where `expires_at <= now()`, deletes the disk
  file for each (from `config('drop-share.disk')`), then deletes the row.
- Scheduled from within the plugin — no host file edits — via:
  ```php
  $this->app->booted(function () {
      $schedule = $this->app->make(Schedule::class);
      $schedule->command(PruneExpiredFilesCommand::class)
          ->everyMinutes(config('drop-share.prune_interval_minutes'));
  });
  ```
  Requires the host's scheduler to actually run (`php artisan schedule:work`
  in dev, or a cron entry hitting `schedule:run` in prod) — documented in
  the plugin README as an operational prerequisite; not this plugin's
  concern to provision.
- Downloads/lookups also defensively treat any row with a past `expires_at`
  as not-found, even if the scheduled prune hasn't run yet — so a slow
  scheduler never serves an expired file.

## Security Summary

- Files are stored outside any publicly-served disk by default; the only
  access path is the phrase-gated download route.
- Content is encrypted at rest (AES-256-CBC via `APP_KEY`); plaintext exists
  only transiently in memory during upload processing and download response
  generation.
- 4-word GenPhrase entropy plus per-IP throttling on the download route
  mitigates phrase brute-forcing.
- Per-IP throttling on the upload route mitigates storage-exhaustion abuse.
- Standard Laravel CSRF protection applies to both forms (no API/AJAX
  surface, no CSRF exemption needed).
- Upload size is capped by both Laravel validation (config-driven) and
  documented as also requiring PHP's `upload_max_filesize`/`post_max_size`
  to allow it (operational caveat, not app-enforceable).
- No file-type restriction, no malware/virus scanning — explicitly
  documented as out of scope in the plugin README so it's a visible,
  intentional gap rather than an oversight.
- Error messages for invalid/expired phrases are identical (no oracle for
  "this phrase existed but expired" vs. "never existed").

## Testing (`plugins/drop-share/tests/`)

The repo has no existing shared scaffold for booting a full Laravel app
inside a plugin's own test suite (no `orchestra/testbench` anywhere yet, per
the `create-plugin` skill). This plugin's routes/throttling/streaming
behavior can't be verified as pure logic, so it adds `orchestra/testbench`
as its own `require-dev` dependency and a plugin-local
`tests/TestCase.php` extending `Orchestra\Testbench\TestCase`, registering
`CoreServiceProvider`, `UiServiceProvider`, and `DropShareServiceProvider`
for the test app. This is scoped to `drop-share` only — no shared/host
test base is introduced, so it sets no precedent other plugins are forced
to follow.

- **UploadTest**: successful upload creates an encrypted file on
  `Storage::fake(config('drop-share.disk'))` and a DB row with a valid
  phrase; oversized file (relative to configured `max_upload_kb`) fails
  validation; uploading rapidly past the rate limit gets throttled (429).
- **DownloadTest**: valid phrase streams the correct decrypted bytes with
  the original filename; invalid phrase shows an error; expired phrase (row
  present but `expires_at` in the past) is treated as not-found; rapid
  requests past the rate limit get throttled (429).
- **PruneExpiredFilesTest**: expired rows and their disk files are deleted;
  non-expired rows/files are untouched.
- **ToolRegistrationTest**: `GET /drop-share` renders both forms and the
  tool registers itself into `ToolRegistry` with the right `name()` /
  `description()` / `url()`, proving the plugin boots end-to-end.

## Open Items / Caveats (documented, not blocking)

- Encryption key is the host's shared `APP_KEY` — acceptable for this
  tool's threat model (short-lived, anonymous file drops) but worth knowing
  if this pattern is reused for something more sensitive later.
- No per-file storage quota / total-disk-usage cap — if abuse outpaces the
  rate limit and default 24h lifespan, that's a follow-up, not blocking
  this build.
- `orchestra/testbench` is introduced solely as this plugin's own
  `require-dev` dependency; it is not proposed as a repo-wide testing
  convention.
