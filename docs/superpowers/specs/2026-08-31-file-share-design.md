# File Share Tool — Design

## Problem Statement

We want a public-facing tool, `plugins/file-share`, that lets an anonymous
visitor upload any file (under a size limit), receive a unique random
passphrase for it, and lets anyone with that phrase download the file again.
Entries expire automatically (default 24h, configurable) and are purged —
both the database row and the file on disk — after expiry. Because this is a
public, unauthenticated, write-accepting endpoint, it needs rate limiting and
encryption-at-rest to be safe to expose.

## Scope

In scope: the `file-share` plugin (upload, phrase generation, retrieval,
expiry/cleanup, rate limiting, at-rest encryption), plugin-owned tests, and
plugin-owned docs (README).

Out of scope: virus/malware scanning, file-type restriction (any file type is
allowed, size-limited only), authentication/ownership of uploads, admin UI to
browse/manage uploads, multi-region/S3 storage (local disk only for now,
though the disk is config-driven so swapping later is cheap).

## Architecture

Standard plugin shape (matches `plugins/hello-tool`), added as
`plugins/file-share/`, Composer package `techysavvy/file-share`, requiring
`techysavvy/core`, `techysavvy/ui`, and `genphrase/genphrase`.

```
plugins/file-share/
├── composer.json
├── src/
│   ├── FileShareServiceProvider.php
│   ├── FileShareTool.php                 # implements ToolContract
│   ├── Models/SharedFile.php
│   ├── Services/PhraseGenerator.php      # wraps GenPhrase
│   ├── Services/FileShareService.php     # store/retrieve/prune logic, encryption
│   ├── Http/Controllers/UploadController.php
│   ├── Http/Controllers/DownloadController.php
│   ├── Http/Requests/StoreUploadRequest.php
│   └── Console/PruneExpiredFilesCommand.php
├── routes/web.php
├── resources/views/home.blade.php
├── config/file-share.php
├── database/migrations/xxxx_create_file_share_uploads_table.php
└── tests/Feature/{UploadTest,DownloadTest,PruneExpiredFilesTest}.php
```

`FileShareServiceProvider::boot()` wires everything at runtime — routes,
views (`file-share::` namespace), config, migrations, a runtime-registered
private disk, named rate limiters, the scheduled prune command, and
registration into `ToolRegistry`. Nothing in `host/` is edited.

## Data Model

Single table `file_share_uploads`:

| column | type | notes |
|---|---|---|
| id | bigint pk | |
| phrase | string, unique, indexed | GenPhrase output, e.g. `correct-horse-battery-staple` |
| disk_path | string | random UUID-based path on the `file-share` disk; holds **encrypted** bytes |
| original_name | string | restored as the download filename |
| mime_type | string | used for the download `Content-Type` header |
| size | unsigned integer | original (pre-encryption) byte size, for display/validation |
| expires_at | timestamp, indexed | `now() + config('file-share.expiry_hours')` at upload time |
| timestamps | | |

`SharedFile` is a plain Eloquent model, `$fillable` on the columns above, no
relationships. `disk_path` is intentionally decoupled from `phrase` (a
directory listing must never reveal a phrase).

## Storage & Encryption

- A dedicated `file-share` disk is registered at runtime in
  `FileShareServiceProvider::boot()` via
  `Config::set('filesystems.disks.file-share', [...])`, pointed at
  `storage/app/private/file-share` (local driver, not the `public` disk —
  never web-accessible directly).
- On upload, `FileShareService` reads the uploaded file's contents and
  encrypts them with Laravel's `Crypt` facade (`Crypt::encryptString`, AES-256-CBC,
  keyed off `APP_KEY`) before writing the ciphertext to the disk. Plaintext
  is never persisted.
- On a valid, non-expired download request, the ciphertext is read back,
  decrypted in-memory (`Crypt::decryptString`), and returned as a download
  response (`Content-Disposition: attachment`, original filename/mime from
  the DB row). Decrypted bytes are never written to disk.
- Whole-file in-memory encrypt/decrypt is acceptable given the 25MB cap
  (default `max_upload_kb`).
- This ties confidentiality to the host's `APP_KEY`; documented as a caveat
  in the plugin README (rotating `APP_KEY` invalidates in-flight uploads —
  acceptable given the 24h default expiry).

## Phrase Generation

`PhraseGenerator` wraps `GenPhrase\Generator` configured for 4 words,
hyphen-separated (e.g. `correct-horse-battery-staple`). On the rare event of
a phrase collision against an existing non-expired row, regenerate and retry
(bounded retries, e.g. 5, then throw — surfaced as a 500 so it's visible
rather than silently colliding).

## Config (`config/file-share.php`, env-overridable)

```php
return [
    'max_upload_kb' => env('FILE_SHARE_MAX_UPLOAD_KB', 25600), // 25MB
    'expiry_hours' => env('FILE_SHARE_EXPIRY_HOURS', 24),
    'disk' => env('FILE_SHARE_DISK', 'file-share'),
    'prune_interval_minutes' => env('FILE_SHARE_PRUNE_INTERVAL_MINUTES', 5),
];
```

## Routes & Controllers

- `GET /file-share` — home page, two-column grid layout (upload form |
  retrieval form), rendered by `resources/views/home.blade.php` using
  `<x-brand::layout>`.
- `POST /file-share/upload` — `UploadController@store`. Validated by
  `StoreUploadRequest` (`file`, `max:<max_upload_kb>`). On success: encrypts
  and stores the file, creates the `SharedFile` row, redirects back to
  `GET /file-share` with the phrase flashed into the session and rendered in
  the upload column (no JS/API needed — plain form POST + redirect).
  Throttled via a named rate limiter, `5` requests/minute per IP.
- `POST /file-share/download` — `DownloadController@store`. Takes `phrase`.
  - Not found or expired → flashed error, redisplay the retrieval form (no
    information disclosure about *why* — same message either way).
  - Found and valid → decrypt and stream the file as an attachment.
  - Throttled via a named rate limiter, `10` requests/minute per IP.

Rate limiters are defined in `FileShareServiceProvider::boot()` via
`RateLimiter::for('file-share-uploads', ...)` /
`RateLimiter::for('file-share-downloads', ...)`, keyed by `Request::ip()`,
and applied to the routes via `throttle:file-share-uploads` /
`throttle:file-share-downloads` middleware.

## Expiry & Cleanup

- `php artisan file-share:prune` (plugin-owned Artisan command): finds all
  `file_share_uploads` rows where `expires_at <= now()`, deletes the disk
  file for each, then deletes the row.
- Scheduled from within the plugin — no host file edits — via:
  ```php
  $this->app->booted(function () {
      $schedule = $this->app->make(Schedule::class);
      $schedule->command(PruneExpiredFilesCommand::class)
          ->everyMinutes(config('file-share.prune_interval_minutes'));
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

- Files are stored outside any publicly-served disk; the only access path is
  the phrase-gated download route.
- Content is encrypted at rest (AES-256-CBC via `APP_KEY`); plaintext exists
  only transiently in memory during upload processing and download response
  generation.
- 4-word GenPhrase entropy plus per-IP throttling on the download route
  mitigates phrase brute-forcing.
- Per-IP throttling on the upload route mitigates storage-exhaustion abuse.
- Standard Laravel CSRF protection applies to both forms (no API/AJAX
  surface, no CSRF exemption needed).
- Upload size is capped by both Laravel validation and documented as also
  requiring PHP's `upload_max_filesize`/`post_max_size` to allow it
  (operational caveat, not app-enforceable).
- No file-type restriction, no malware/virus scanning — explicitly
  documented as out of scope in the plugin README so it's a visible,
  intentional gap rather than an oversight.
- Error messages for invalid/expired phrases are identical (no oracle for
  "this phrase existed but expired" vs. "never existed").

## Testing (`plugins/file-share/tests/Feature/`)

- **UploadTest**: successful upload creates an encrypted file on
  `Storage::fake('file-share')` and a DB row with a valid phrase; oversized
  file fails validation; uploading rapidly past the rate limit gets throttled
  (429).
- **DownloadTest**: valid phrase streams the correct decrypted bytes with the
  original filename; invalid phrase shows an error; expired phrase (row
  present but `expires_at` in the past) is treated as not-found; rapid
  requests past the rate limit get throttled (429).
- **PruneExpiredFilesTest**: expired rows and their disk files are deleted;
  non-expired rows/files are untouched.
- A route-reachability test asserting `GET /file-share` renders both forms,
  proving the plugin boots end-to-end (routes + views + `ui` layout
  component all wired).

## Open Items / Caveats (documented, not blocking)

- Encryption key is the host's shared `APP_key` — acceptable for this
  tool's threat model (short-lived, anonymous file drops) but worth knowing
  if this pattern is reused for something more sensitive later.
- No per-file storage quota / total-disk-usage cap — if abuse outpaces the
  rate limit and 24h expiry, that's a follow-up, not blocking this build.
