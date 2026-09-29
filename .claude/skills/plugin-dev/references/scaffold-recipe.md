# Scaffold recipe

Task 0 of every new-plugin plan. Produces a plugin that boots, registers its card on `/`, and renders a stub page. No feature logic. Names come from `conventions.md` → Derived names. Keep `plugins/hello-tool/` (minimal) and `plugins/qr-forge/` (tests + assets) open as live references.

1. **`plugins/<kebab>/composer.json`**
   ```json
   {
       "name": "techysavvy/<kebab>",
       "description": "<one sentence>",
       "type": "library",
       "version": "1.0.0",
       "repositories": [{ "type": "path", "url": "../*", "options": { "symlink": true } }],
       "require": {
           "php": "^8.4",
           "illuminate/support": "^13.0",
           "techysavvy/core": "*",
           "techysavvy/ui": "*"
       },
       "require-dev": {
           "orchestra/testbench": "^11.2",
           "phpunit/phpunit": "^12.5"
       },
       "autoload": { "psr-4": { "Techysavvy\\<Studly>\\": "src/" } },
       "autoload-dev": { "psr-4": { "Techysavvy\\<Studly>\\Tests\\": "tests/" } },
       "extra": { "laravel": { "providers": ["Techysavvy\\<Studly>\\<Studly>ServiceProvider"] } },
       "minimum-stability": "stable"
   }
   ```
   Drop `repositories`/`require-dev`/`autoload-dev` only if the spec says the plugin gets no plugin-level tests (rare — see `testing.md`).

2. **`src/<Studly>.php`** — implements `Techysavvy\Core\ToolContract`.

3. **`src/<Studly>ServiceProvider.php`** — `boot()` in order: `loadRoutesFrom(__DIR__.'/../routes/web.php')`, `loadViewsFrom(__DIR__.'/../resources/views', '<kebab>')`, `$this->app->make(ToolRegistry::class)->register(new <Studly>())`. `boot()`, not `register()`: core binds the registry singleton in its `register()`, and all `register()`s run before any `boot()`. (Asset registration is added here later, by the task that introduces JS — not in the scaffold.)

4. **`routes/web.php`** — `Route::get('/<kebab>', fn () => view('<kebab>::home'))->name('<kebab>.home');`. The tool's whole URL surface lives in this file.

5. **`resources/views/home.blade.php`** — stub: `<x-ui::layout title="<Name>">` + `<x-ui::page-header>` with a one-line placeholder.

6. **`phpunit.xml` + `tests/TestCase.php` + `tests/Feature/<Studly>Test.php`** — copy `plugins/qr-forge/`'s, swapping the provider/namespace. First tests: registers in `ToolRegistry`; `url()` responds 200 and shows `name()`.

7. **Wire into host** — add `"techysavvy/<kebab>": "*"` to `host/composer.json` `require` next to the other `techysavvy/*` entries, then:
   ```
   composer update techysavvy/<kebab> --working-dir=host
   composer install --working-dir=plugins/<kebab>
   ```

8. **CI** — add a "Run <kebab> plugin tests" step to `.github/workflows/ci.yml` mirroring qr-forge's.

9. **Leave `host/tests/Feature/ToolListingTest.php` alone** — it loops `ToolRegistry::all()` and already covers the new tool.

10. **Verify** — everything in `verify.md`, then commit: `feat(plugins): scaffold <kebab> tool plugin`.
