# Plugin conventions

Single source of repo facts for the plugin-dev skill family. When the repo changes (new `ui` component, new core API), update this file — not the individual skills.

Authoritative background: `CLAUDE.md` (the rules PRs are reviewed against) and `ARCHITECTURE.md` (the why).

## The boundary

| Rule | Consequence |
|---|---|
| `host/` holds no tool-specific logic | A tool's routes, views, controllers, config, migrations, JS and CSS live in `plugins/<kebab>/`. |
| A plugin's public surface is its `ServiceProvider` + `ToolContract` impl | Nothing in `host/` or another plugin imports a plugin's `src/` classes. |
| Tools self-register | `ToolRegistry::register()` inside the plugin's `ServiceProvider::boot()` — never a list in `host/`. |
| Branding via `ui` | Use `<x-ui::...>` components; don't duplicate their markup/styles. |
| Composer runs from `host/` | `composer ... --working-dir=host` or `cd host && composer ...`. |
| Provider auto-discovery | `extra.laravel.providers` in the plugin's `composer.json`. Never edit `host/config/app.php`. |

Root-level files that *list* plugins (`Makefile` `install` target, `.github/workflows/ci.yml`) are build plumbing, not tool logic — a plugin that owns an npm build or its own phpunit suite must be added there.

## Derived names

| Use | Format | Example ("Word Counter") |
|---|---|---|
| `plugins/` dir, composer package suffix, route name prefix, view namespace, asset bundle name | kebab-case | `word-counter` |
| PHP namespace segment, class prefix | StudlyCase | `WordCounter` → `Techysavvy\WordCounter\WordCounter` |
| `name()` | Title Case, as chosen | `Word Counter` |
| `icon()` | one emoji | `🔤` |
| `description()` | one sentence, what it does | `Paste text and see live word and character counts.` |

## ToolContract

`Techysavvy\Core\ToolContract`: `icon(): string`, `name(): string`, `description(): string`, `url(): string` (return `route('<kebab>.home')`).

## UI components

`plugins/ui/resources/views/components/` is the live list — check it before assuming a component exists. Currently: `layout`, `page-header`, `panel`, `button`, `input`, `alert`, `dropzone`, `tabs`, `logo`, `tool-card`, `tool-grid`.

Anything else is plain Tailwind utilities using the tokens in `plugins/ui/resources/css/theme.css` (`text-ink`, `bg-surface`, `rounded-brand`, `border-steel-300`, `signal-*`, …). Host's Tailwind build already scans every plugin's Blade views (`@source '../../vendor/techysavvy/*/resources/views/**/*.blade.php'`), so utility classes in plugin views need no plugin CSS build.

## Plugin assets (JS/CSS)

Only when the tool needs bundled JS/CSS (an npm dependency, non-trivial client logic). A tool with no client behavior needs none of this.

1. Plugin owns its build: `plugins/<kebab>/package.json` (`build: vite build`, `test: node --test tests/js/*.test.js` if JS tests exist) and `vite.config.js` in library mode, IIFE, output to `resources/dist/` — copy `plugins/qr-forge/vite.config.js`.
2. Source in `resources/js/`; built output in `resources/dist/`.
3. In `ServiceProvider::boot()`:
   ```php
   $this->app->make(AssetRegistry::class)->register('<kebab>', new AssetBundle(
       directory: __DIR__.'/../resources/dist',
       scripts: ['<kebab>.js'],   // styles: [...] for CSS; ['x.js' => ['module' => true]] for ESM
   ));
   ```
   (`Techysavvy\Core\Assets\AssetBundle`, `Techysavvy\Core\Assets\AssetRegistry`.)
4. The view opts in with `@pluginAssets('<kebab>')` at the top. The `ui` layout emits the tags.
5. Add `npm install`/`npm run build --prefix plugins/<kebab>` to the `Makefile` `install` target and to CI's install/build steps (and `npm test --prefix ...` if it has JS tests).

Never: hand-written `<script src>`/`<link>` tags for the bundle, inline `<script>`/`<style>` blocks, adding the plugin to `host/`'s Vite build, or `vendor:publish`.

## Plugin documents

Everything a plugin's development produces lives in the plugin:

| Path | What |
|---|---|
| `plugins/<kebab>/docs/SPEC.md` | The plugin's v1 spec (older plugins: `docs/PRD.md` — treat as the spec). |
| `plugins/<kebab>/docs/specs/YYYY-MM-DD-<feature>.md` | Spec for a later feature on an existing plugin. |
| `plugins/<kebab>/docs/plans/YYYY-MM-DD-<slug>.md` | Implementation plan for one spec. |
| `plugins/<kebab>/docs/decisions.md` | Only when real decisions were made; short ADR-style entries. |
| `plugins/<kebab>/README.md` | Current state only; unbuilt features marked Planned. |
