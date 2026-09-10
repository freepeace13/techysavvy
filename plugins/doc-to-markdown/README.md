# Doc to Markdown

Convert a Word document or PDF into clean Markdown. Nothing is stored — the
uploaded file is parsed in-process and the result is handed straight back to
the browser. Built as a standalone tool plugin; see the repo root
`CLAUDE.md` for the overall monorepo shape.

## How it works

1. `GET /doc-to-markdown` — public page with a dropzone for a single
   `.docx` or `.pdf`.
2. `POST /doc-to-markdown/convert` — `ConverterResolver` picks a converter
   from the uploaded file's extension: `DocxConverter` (PHPWord, via
   `DocxMarkdownWriter`) or `PdfConverter` (`smalot/pdfparser`). The
   response is JSON carrying the `markdown` and a suggested `filename`. An
   unreadable file comes back as a 422 with a plain-language message.
3. The browser renders a live preview of that Markdown with
   [markdown-it](https://github.com/markdown-it/markdown-it), bundled by
   this plugin's own Vite build (see below), and offers the raw `.md` as a
   client-side download. No conversion artifact touches disk.

## Installation / assets

This plugin owns its JavaScript: `package.json` declares `markdown-it` as a
real dependency and Vite bundles it, in library mode, into a single
browser-ready IIFE. There is no hand-downloaded dist file.

`make install` at the repo root runs the whole sequence. To do it by hand
from the repo root:

```bash
npm install --prefix plugins/doc-to-markdown          # install markdown-it + vite
npm run build --prefix plugins/doc-to-markdown        # -> resources/dist/doc-to-markdown.js
cd host && php artisan vendor:publish \
    --tag=doc-to-markdown-assets --force              # -> host/public/vendor/doc-to-markdown/
```

Things worth knowing:

- **`resources/dist/` is generated and gitignored.** Never edit a file in
  it. The source of truth is `resources/js/doc-to-markdown.js`, which sets
  the one global the page depends on, `window.docToMarkdownRender`.
- **Editing `resources/js/` requires a rebuild *and* a re-publish** before
  the change shows up in the browser. Skipping either leaves the old bundle
  in `host/public/vendor/` and the page looking unchanged.
- **`--force` is not optional.** `vendor:publish` skips files that already
  exist, so without it a rebuilt bundle never overwrites the published one.
- The bundle is served as a static file from the host's public directory —
  it does not go through a PHP route.
- `host/`'s own Vite build knows nothing about this plugin, and must not:
  tool-specific logic never belongs in `host/`.

## Configuration

| Env var | Config key | Default | Meaning |
|---|---|---|---|
| `DOC_TO_MARKDOWN_MAX_UPLOAD_KB` | `max_upload_kb` | `10240` (10MB) | Max upload size in kilobytes. |

## Caveats

- **PDF conversion preserves text, not formatting.** `smalot/pdfparser`
  extracts a text layer; headings, tables, and styling in the original PDF
  do not survive as Markdown structure. The UI says so on the page.
- A PDF with no text layer (a scan, an image-only export) yields nothing
  useful — there is no OCR here.
- Markdown is rendered with markdown-it's `html: false`, so raw HTML in a
  converted document is escaped rather than passed through to the preview.
- Upload size is also bound by PHP's `upload_max_filesize` and
  `post_max_size` ini settings, which this plugin cannot override. Set them
  at least as high as `DOC_TO_MARKDOWN_MAX_UPLOAD_KB`.
- This plugin's `routes/web.php` explicitly wraps its routes in Laravel's
  `web` middleware group. Routes registered via a plugin `ServiceProvider`'s
  `loadRoutesFrom()` do **not** inherit that group automatically — only
  routes declared directly in `host/routes/web.php` do. Without the explicit
  wrap the upload form's CSRF protection and validation error sharing would
  silently not work.

## Tests

PHP tests use `orchestra/testbench` to boot a real Laravel app, plus plain
PHPUnit for the pure converter logic. The plugin has its own self-contained
`vendor/`, so it runs independently of `host/`. From this directory:

```bash
composer install
vendor/bin/phpunit
```

The built bundle has its own smoke test — it evaluates the real build
output in a `node:vm` sandbox and asserts `window.docToMarkdownRender`
renders Markdown. It requires `npm run build` to have run first:

```bash
npm run build
npm test
```
