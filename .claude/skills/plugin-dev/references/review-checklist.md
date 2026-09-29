# Architecture review checklist

Run against the branch diff (`git diff main...HEAD`). Each item is a finding if violated. Commands are starting points — read the hits, don't count them.

| # | Check | How |
|---|---|---|
| 1 | No tool-specific code in `host/` (only `host/composer.json` + lock may change for a new plugin) | `git diff --stat main...HEAD -- host/` |
| 2 | No imports of another plugin's classes (only `Techysavvy\Core\*`, `Techysavvy\Ui\*`, own namespace) | `grep -rn 'use Techysavvy\\' plugins/<kebab>/src plugins/<kebab>/tests` |
| 3 | Tool registered in `boot()`, not `register()` | read the ServiceProvider |
| 4 | Provider declared in `extra.laravel.providers`; `host/config/app.php` untouched | read composer.json; diff |
| 5 | No hand-written `<script src>`/`<link>` for the bundle, no inline `<script>`/`<style>` | `grep -rnE '<script|<link|<style' plugins/<kebab>/resources/views` |
| 6 | Assets declared via `AssetRegistry` + `@pluginAssets`; plugin not in `host/vite.config.js`; no `vendor:publish` | read provider + view; `git diff main...HEAD -- host/vite.config.js` |
| 7 | UI uses `<x-ui::...>` where a component exists; no copied ui markup | read views |
| 8 | Plugin tests don't extend host's `Tests\TestCase`; host tests don't name this plugin | `grep -rn 'Tests\\TestCase' plugins/<kebab>/tests`; `grep -rn '<Name>' host/tests` |
| 9 | Makefile + CI updated if the plugin has an npm build or its own phpunit suite | read diff of `Makefile`, `.github/workflows/ci.yml` |
| 10 | README states current behavior; unbuilt items marked Planned; spec status updated | read |
