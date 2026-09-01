# Contributing to techysavvy

Thanks for considering a contribution! techysavvy is a Laravel monorepo made
of small, independent tool plugins hosted by a thin bootstrapper app
(`host/`). Most contributions fall into one of three shapes:

- **A new tool** — a new folder under `plugins/`.
- **A fix or enhancement to an existing tool** — changes scoped to that
  tool's `plugins/<name>/` folder.
- **A fix to the host bootstrapper** — changes to `host/` itself (routing,
  the tool registry mechanism, shared layout wiring). These should be rare;
  see the rules below for what does *not* belong here.

Before opening a PR, please read [`CLAUDE.md`](CLAUDE.md) — it documents the
architectural rules this repo enforces (plugin boundaries, where UI
components belong, where tests belong). Those rules apply to human and AI
contributors alike, and reviewers will ask for changes that violate them.

## Ground rules

- **Tool-specific logic never goes in `host/`.** If a change is about one
  tool's behavior, it belongs in that tool's `plugins/<name>/` folder.
- **A plugin's only public surface is its `ServiceProvider`** (routes,
  views, config, migrations it registers) and, if it's a tool, its
  `ToolContract` implementation. Don't reach into another plugin's `src/`
  directly, and don't let `host/` do so either.
- **New tools register themselves.** Implement
  `Techysavvy\Core\ToolContract` and call `ToolRegistry::register()` from
  your plugin's own `ServiceProvider::boot()` — never add a tool to a list
  inside `host/`.
- **Use `plugins/ui`'s Blade components** (`<x-brand::...>`) for
  branding/UI instead of duplicating markup or styles.
- **Tests live with what they test.** A plugin's own behavior is tested in
  `plugins/<name>/tests`; host-level seam tests (e.g. "the home page
  renders installed tools") live in `host/tests`.

## Adding a new tool

`plugins/hello-tool` is a working reference implementation — copy its shape
(`composer.json`, `ServiceProvider`, `ToolContract` implementation, routes,
views) when scaffolding a new tool. If you're using Claude Code, the
`create-plugin` skill automates this scaffolding and the `host/composer.json`
wiring.

If you're planning a substantial new tool, please open an issue first using
the "New tool proposal" template so the scope can be discussed before you
invest time in a PR.

## Development setup

```sh
make install   # composer install + npm install inside host/ and plugins/ui
make serve     # php artisan serve
make test      # php artisan test
```

Composer commands run with `host/` as the working directory
(`composer install --working-dir=host`, or `cd host && composer ...`).

## Submitting a change

1. Fork the repo and create a branch off `main`.
2. Make your change, following the ground rules above.
3. Add or update tests in the correct location (see "Ground rules").
4. Run `make test` and `cd host && ./vendor/bin/pint` locally — both must
   pass before you open a PR; CI re-checks the same two commands.
5. Open a PR against `main` and fill in the PR template. Keep PRs focused:
   one tool or one fix per PR is easier to review than a bundle of
   unrelated changes.

## Questions and bugs

Open an issue using the appropriate template (bug report or new tool
proposal). For anything that doesn't fit those, open a blank issue and
describe what you're trying to do.
