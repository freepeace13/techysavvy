---
name: create-plugin
description: Use when creating a brand-new tool/plugin under plugins/ in the techysavvy monorepo — proposes names, interviews the user to shape the spec, scaffolds the minimal Composer package skeleton (no feature implementation), wires it into host/composer.json and the ToolRegistry, then writes the plugin's README and PRD/docs.
---

# Create Plugin

## Overview

Every tool in this repo is a standalone Composer package under `plugins/<name>/`, wired into `host/` via a path repository. `plugins/hello-tool/` is the canonical reference shape — this skill is that recipe made explicit and repeatable, plus the wiring/verification steps that are easy to forget.

A plugin is "done" only when it shows up as a card on the host home page (`/`) — not just when the folder exists.

## Workflow

Four phases, in order. Do not skip ahead — each phase's output feeds the next.

1. **Name** — settle the plugin name.
2. **Spec** — establish what the plugin does, with the user.
3. **Skeleton** — scaffold the minimal package (Recipe below). Skeleton only.
4. **Docs** — write the plugin's README and PRD/reference docs.

### Phase 1 — Name

- **Name given explicitly** ("call it word-counter" / "Word Counter tool") → use it. Check `plugins/` for a collision first.
- **No name given** (only a spec/prompt, or nothing) → propose **3** candidate names (your top 3) and let the user choose (use `AskUserQuestion`; the user can always pick "Other"). Brainstorm a longer list privately first (aim for ~8-10), aiming for names that sound **fancy yet sensible**: polished, brandable, and easy to say, but still clearly hinting at what the tool does (no cryptic coinages or empty buzzwords). Keep them short (1-3 words) and don't invent scope beyond what the user said. Rank them on that fancy-and-sensible balance and present only your **top 3** (more only if the user asks for more). Give a one-line rationale per name and mark your favorite as recommended. Don't scaffold or write anything until a name is chosen.

### Phase 2 — Spec

Help the user establish the plugin's spec. Don't dump a questionnaire and don't invent requirements silently. Two modes, mixed as fits:

- **Ask** — short, focused questions (1-4 per round via `AskUserQuestion`, prefer multiple choice), one topic per round.
- **Recommend** — when the user is unsure or the answer is conventional, propose a concrete default with a one-line reason and ask them to confirm or adjust. Lead with your recommendation.

Cover, skipping anything the user already answered:

| Topic | What to pin down |
|---|---|
| Problem & audience | What job does it do, for whom, and why a tool page rather than something else |
| Core behavior | Inputs, outputs, the main user flow (happy path) |
| Scope | What's explicitly in v1 and what's out (non-goals) |
| Data & privacy | Is anything uploaded, stored, or sent to a third party? Retention? Client-side only vs server-side processing |
| Dependencies | Composer/npm packages, external services, PHP extensions, API keys/env vars |
| Persistence | Migrations/models needed, or stateless? |
| Routes & UI | Pages/endpoints beyond `<kebab-name>.home`; which `<x-ui::...>` components apply |
| Config | Limits, feature flags, `config/<kebab-name>.php` keys |
| Testing | Is there real logic worth plugin-level unit tests (Recipe step 7)? |
| Success criteria | How we know v1 is done |

Stop asking once you have enough to write the PRD and scaffold. Then **play the spec back** as a compact summary (name, one-line description, icon, core flow, scope/non-goals, dependencies, persistence, routes) and get an explicit go-ahead before Phase 3. Fold in corrections.

### Phase 3 — Skeleton

Run the Recipe below. **Skeleton only**: the minimal Laravel package files needed for the plugin to boot, register its card, and render a placeholder page. Do **not** implement the feature — no business logic, converters, models, migrations, controllers with real behavior, or JS. Where the spec calls for those, leave them for later and record them in the PRD's milestones/backlog instead. The home view is a stub (`<x-ui::layout>` with a heading and a one-line "coming soon"-style placeholder), not the finished UI.

### Phase 4 — Docs

After the skeleton is wired and verified, write the docs (see "Docs" below), drawing on the agreed spec.

## Derived names

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
           "php": "^8.4",
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

5. **`plugins/<kebab-name>/resources/views/home.blade.php`** — a **stub**: wrap in `<x-ui::layout title="...">` with a heading and one-line placeholder; the real UI is out of scope for the skeleton. For when the UI is built later: `ui` exposes `<x-ui::layout>`, `<x-ui::page-header>`, `<x-ui::panel>`, `<x-ui::button>`, `<x-ui::input>`, `<x-ui::alert>`, `<x-ui::dropzone>`, `<x-ui::tabs>`, `<x-ui::logo>`, `<x-ui::tool-card>`, `<x-ui::tool-grid>` (check `plugins/ui/resources/views/components/` for the current set before assuming more exist) — everything else in the tool's own UI (e.g. stat tiles) is plain Tailwind utility classes matching the tokens in `plugins/ui/resources/css/theme.css` (`text-ink`, `bg-surface`, `border-brand-100`, `rounded-brand`, etc). **No plugin-level Vite/CSS/JS config is needed or wanted** — `host/resources/css/app.css` already has `@source '../../vendor/techysavvy/*/resources/views/**/*.blade.php';`, so Tailwind classes used in any plugin's Blade views are picked up automatically the next time `host` builds its assets. For interactivity, inline `<script>` in the Blade view is the norm (see `hello-tool`/existing tools) — don't add a `resources/js/` + Vite entry for a plugin unless the tool genuinely needs a bundled JS dependency.

6. **Wire into host** — add `"techysavvy/<kebab-name>": "*"` to `host/composer.json`'s `require` block (alongside the other `techysavvy/*` entries), then run, from repo root:
   ```
   composer update techysavvy/<kebab-name> --working-dir=host
   ```
   This symlinks the plugin into `host/vendor/`, regenerates `host/composer.lock`, and runs `artisan package:discover` (a `post-autoload-dump` script) so the new `ServiceProvider` is auto-registered — never edit `host/config/app.php`.

7. **(Optional) plugin-level unit tests** — `plugins/<kebab-name>/tests/` with a standalone `phpunit.xml` (`bootstrap="../../host/vendor/autoload.php"`, `<testsuite><directory>tests</directory></testsuite>`, `<source><include><directory>src</directory></include></source>`) plus `"phpunit/phpunit": "^12.5"` in `require-dev` and a PSR-4 `autoload-dev` entry for the test namespace. Only add this when the plugin has real business logic worth unit-testing (e.g. a calculation class) — a thin tool with no logic beyond rendering a view doesn't need it, matching `hello-tool`, which has none.

8. **Leave `host/tests/Feature/ToolListingTest.php` alone.** It's already written registry-driven (it loops `ToolRegistry::all()`, asserting whatever's registered renders and responds) specifically so it never needs editing when a plugin is added or removed — see Testing below. Do not add this tool's name/description/route to it.

## Docs

Write these after the skeleton verifies, from the spec agreed in Phase 2. Keep them accurate to what exists *now* versus what is *planned* — mark unbuilt features clearly (e.g. "Planned") so the README never claims behavior the skeleton doesn't have.

- **`plugins/<kebab-name>/README.md`** — what the tool is (one paragraph), status (skeleton / in progress), install & wiring (path repo + `composer update techysavvy/<kebab-name> --working-dir=host`), routes, config keys and env vars, dependencies, directory layout, how to run the plugin's tests, and a link to the PRD. Match the tone/structure of `plugins/doc-to-markdown/README.md`.
- **`plugins/<kebab-name>/docs/PRD.md`** — problem & audience, goals, non-goals, user stories / core flow, functional requirements, data & privacy notes, dependencies, routes/UI outline, open questions, success criteria, and a milestone list breaking the remaining implementation into ordered steps (this is the handoff for the next session).
- **Other references, only when the spec warrants them**: `docs/architecture.md` (non-trivial internals, e.g. a pipeline of converters), `docs/decisions.md` (short ADR-style notes on choices made during Phase 2, e.g. "client-side only because of privacy"). Don't create empty or boilerplate files.

Finish by giving the user a short summary: chosen name, files created, verification results, and the PRD's first milestone as the suggested next step.

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
| Scaffolding before the user picked a name or confirmed the spec | Finish Phases 1-2 first; the skeleton is cheap to get right and annoying to rename. |
| Implementing the feature during scaffolding | Skeleton only — stub view, no real logic. Put remaining work in the PRD milestones. |
| README describing features that don't exist yet | Mark unbuilt behavior as Planned; the README states current status. |
| Proposing fewer than 3 names, dumping a long unranked list, or picking one for the user | Brainstorm many, present your top 3 (fancy yet sensible) with rationale, and let the user choose. |
