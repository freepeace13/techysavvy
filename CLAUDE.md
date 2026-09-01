# techysavvy

Laravel monorepo. `host/` is the only runnable app; `plugins/*` are standalone Composer packages wired in via path repositories. Full spec: `ARCHITECTURE.md`.

## Rules when working in this repo

- Never put tool-specific logic in `host/`. If a change is about one tool's behavior, it belongs in that tool's `plugins/<name>/` folder.
- A plugin's only public surface to the rest of the system is its `ServiceProvider` (routes/views/config/migrations it registers) and, if it's a tool, its `ToolContract` implementation. Nothing in `host/` or another plugin should reach into a plugin's `src/` directly.
- New tools implement `Techysavvy\Core\ToolContract` and register themselves via `ToolRegistry::register()` inside their own `ServiceProvider::boot()` — never add a tool to a list inside `host/`.
- Branding/UI: use `plugins/ui`'s Blade components (`<x-brand::...>`) instead of duplicating markup/styles in a plugin or in `host/`.
- Composer commands run with `host/` as the working directory (`composer install --working-dir=host`, or `cd host && composer ...`). The Makefile wraps the common ones.
- Tests belong with what they test: host-level seam tests (e.g. "the home page renders installed tools") live in `host/tests`; a plugin's own behavior is tested inside `plugins/<name>/tests`.
- `plugins/hello-tool` is a working reference implementation — copy its shape (composer.json, ServiceProvider, ToolContract implementation, routes, views) when scaffolding a new tool.
