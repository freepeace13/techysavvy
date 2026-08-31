---
name: create-plugin
description: Use when scaffolding a brand-new tool/plugin under plugins/ in the techysavvy monorepo — creating the standard Composer package shape, wiring it into host/composer.json, and registering it in the ToolRegistry so it shows up as a card on the home page.
---

# Create Plugin

## Overview

Every tool in this repo is a standalone Composer package under `plugins/<name>/`, wired into `host/` via a path repository. `plugins/hello-tool/` is the canonical reference shape — this skill is that recipe made explicit and repeatable, plus the wiring/verification steps that are easy to forget.

A plugin is "done" only when it shows up as a card on the host home page (`/`) — not just when the folder exists.

## Inputs

- **Name given explicitly** ("call it word-counter" / "Word Counter tool") → use it.
- **No name given, only a spec/prompt** ("a tool that counts words in pasted text") → propose 2-3 candidate names (short, easy to remember, clearly matching the tool's description — 2-3 words max, no invented scope beyond the spec) and let the user pick before scaffolding anything.

From the chosen name, derive every casing you'll need:

| Use | Format | Example (name: "Word Counter") |
|---|---|---|
| `plugins/` dir, composer package suffix, route name prefix, view namespace | kebab-case | `word-counter` |
| PHP namespace segment, class prefix | StudlyCase | `WordCounter` |
| Display `name()` | Title Case, as given | `Word Counter` |
| `icon()` | one emoji capturing the tool | `🔤` |
| `description()` | one sentence, what it does | `Paste text and see live word and character counts.` |

## Recipe

Do these in order. Use `plugins/hello-tool/` open in another read as a live reference for exact shape.

1. **`plugins/<kebab-name>/composer.json`**
   ```json
   {
       "name": "techysavvy/<kebab-name>",
       "description": "<one sentence>",
       "type": "library",
       "version": "1.0.0",
       "require": {
           "php": "^8.3",
           "illuminate/support": "^13.0",
           "techysavvy/core": "*",
           "techysavvy/ui": "*"
       },
       "autoload": {
           "psr-4": { "Techysavvy\\<StudlyName>\\": "src/" }
       },
       "extra": {
           "laravel": { "providers": ["Techysavvy\\<StudlyName>\\<StudlyName>ServiceProvider"] }
       },
       "minimum-stability": "stable"
   }
   ```

2. **`plugins/<kebab-name>/src/<StudlyName>.php`** — implements `Techysavvy\Core\ToolContract` (`icon()`, `name()`, `description()`, `url(): route('<kebab-name>.home')`).

3. **`plugins/<kebab-name>/src/<StudlyName>ServiceProvider.php`** — `boot()` calls, in order: `loadRoutesFrom(__DIR__.'/../routes/web.php')`, `loadViewsFrom(__DIR__.'/../resources/views', '<kebab-name>')`, then `$this->app->make(ToolRegistry::class)->register(new <StudlyName>())`. Register in `boot()`, not `register()` — registering here relies on `core`'s `register()` phase having already bound the singleton, and every provider's `register()` runs before any provider's `boot()`.

4. **`plugins/<kebab-name>/routes/web.php`** — one `Route::get('/<kebab-name>', fn () => view('<kebab-name>::home'))->name('<kebab-name>.home');`. Keep the tool's whole URL surface here; add more routes to this same file as needed.

5. **`plugins/<kebab-name>/resources/views/home.blade.php`** — wrap in `<x-brand::layout title="...">`. `ui` currently only exposes `<x-brand::layout>`, `<x-brand::logo>`, `<x-brand::tool-card>`, `<x-brand::tool-grid>` (check `plugins/ui/resources/views/components/` for the current set before assuming more exist) — everything else in the tool's own UI (textareas, buttons, stat tiles) is plain Tailwind utility classes matching the tokens in `plugins/ui/resources/css/theme.css` (`text-ink`, `bg-surface`, `border-brand-100`, `rounded-brand`, etc). **No plugin-level Vite/CSS/JS config is needed or wanted** — `host/resources/css/app.css` already has `@source '../../vendor/techysavvy/*/resources/views/**/*.blade.php';`, so Tailwind classes used in any plugin's Blade views are picked up automatically the next time `host` builds its assets. For interactivity, inline `<script>` in the Blade view is the norm (see `hello-tool`/existing tools) — don't add a `resources/js/` + Vite entry for a plugin unless the tool genuinely needs a bundled JS dependency.

6. **Wire into host** — add `"techysavvy/<kebab-name>": "*"` to `host/composer.json`'s `require` block (alongside the other `techysavvy/*` entries), then run, from repo root:
   ```
   composer update techysavvy/<kebab-name> --working-dir=host
   ```
   This symlinks the plugin into `host/vendor/`, regenerates `host/composer.lock`, and runs `artisan package:discover` (a `post-autoload-dump` script) so the new `ServiceProvider` is auto-registered — never edit `host/config/app.php`.

7. **(Optional) plugin-level unit tests** — `plugins/<kebab-name>/tests/` with a standalone `phpunit.xml` (`bootstrap="../../host/vendor/autoload.php"`, `<testsuite><directory>tests</directory></testsuite>`, `<source><include><directory>src</directory></include></source>`) plus `"phpunit/phpunit": "^12.5"` in `require-dev` and a PSR-4 `autoload-dev` entry for the test namespace. Only add this when the plugin has real business logic worth unit-testing (e.g. a calculation class) — a thin tool with no logic beyond rendering a view doesn't need it, matching `hello-tool`, which has none.

8. **Leave `host/tests/Feature/ToolListingTest.php` alone.** It's already written registry-driven (it loops `ToolRegistry::all()`, asserting whatever's registered renders and responds) specifically so it never needs editing when a plugin is added or removed — see Testing below. Do not add this tool's name/description/route to it.

## Testing

**Never hardcode a specific plugin's name, description, or route into a host-level test.** `host/tests/Feature/ToolListingTest.php` is a host-owned seam test — its job is "the home page renders whatever's installed," not "Word Counter is installed." A test asserting `assertSee('Word Counter')` breaks the moment that plugin's folder/composer entry is removed, for a reason that has nothing to do with a host regression — that's a false failure. Keep host tests parametric over `ToolRegistry::all()` instead (loop over whatever's registered, assert each tool's `name()`/`description()`/`url()` show up and its own `url()` responds); this covers every current and future plugin with zero edits per plugin.

A plugin's own behavior — "does my route respond," "does my view render the right content," "did my ServiceProvider register the right ToolContract values" — is that plugin's responsibility to prove, inside `plugins/<kebab-name>/tests/`, not the host's. Two tiers, pick what fits:

- **Pure logic** (no HTTP/view/container needed): a plain PHPUnit test per step 7 above — this is the common case for most tools.
- **Route/view/registration behavior**: this needs a booted Laravel app. Nothing in this repo currently exposes a shared, reusable test base for that (no `orchestra/testbench` or equivalent shared `TestCase` exists yet in `plugins/core` or `plugins/ui`) — don't invent a one-off, heavyweight bootstrapping scheme inside a single plugin's `tests/` to work around that gap. If you need this tier, say so explicitly rather than silently skipping it or silently reaching into `host/`'s `Tests\TestCase` (a plugin depending on `host/` inverts the dependency direction the whole architecture is built on). The registry-driven host seam test in step 8 already gives you route+render coverage for free once the plugin is wired in — that's usually enough.

## Verify

If `host/.env` or built frontend assets don't exist yet (fresh checkout/worktree), `php artisan test` fails on `APP_KEY`/`ViteManifestNotFoundException` for reasons unrelated to your plugin — run `cp .env.example .env && php artisan key:generate` and `npm install --ignore-scripts && npm run build` inside `host/` first if so.

Run from repo root, in order — don't report done without these:
```
composer update techysavvy/<kebab-name> --working-dir=host   # symlinks + package:discover
cd host && php artisan route:list --name=<kebab-name>        # confirms the route registered
php artisan test                                             # ToolListingTest picks up the new tool automatically
```
If a dev server is running, load `/` and confirm the new card renders with the right icon/name/description and that clicking it lands on the tool's own page.

## Common Mistakes

| Mistake | Fix |
|---|---|
| Registering the tool in `ServiceProvider::register()` instead of `boot()` | `ToolRegistry` singleton isn't guaranteed bound yet during `register()` phase across providers — use `boot()`. |
| Forgetting to add the package to `host/composer.json` `require` | Plugin folder existing isn't enough — host only autoloads packages it requires. |
| Adding a `resources/js`/Vite entry per plugin | Not needed — host's single Tailwind build already globs every `techysavvy/*` plugin's Blade views via `@source`. |
| Editing `host/config/app.php` to register the provider | Never — Composer package auto-discovery (`extra.laravel.providers` + `package:discover`) handles it. |
| Reaching into another plugin's `src/` or into `host/app/` | A plugin's only public surface is its `ServiceProvider` + `ToolContract` impl. |
| Adding this tool's name/description/route as new assertions in `host/tests/Feature/ToolListingTest.php` | That test is already registry-driven and covers every tool generically — editing it per plugin reintroduces a coupling that breaks the test the moment a plugin is removed. Leave it alone. |
| Writing a plugin test that extends `Tests\TestCase` from `host/` | Inverts the dependency direction — a plugin must never depend on `host/`. |
