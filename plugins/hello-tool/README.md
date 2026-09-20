# Hello Tool

Demo tool plugin and the **reference implementation** for the plugin folder
shape. It does nothing useful on purpose: it proves a tool can register
itself with the `ToolRegistry`, show up as a card on the host home page and
serve its own page. Copy its shape when scaffolding a new tool (see the
`create-plugin` skill and the repo root `CLAUDE.md`).

**Status: complete.** It is intentionally a skeleton and will not grow
features.

## Routes

| Route | Name | Description |
|---|---|---|
| `GET /hello-tool` | `hello-tool.home` | Static page rendered with `<x-ui::layout>` and `<x-ui::page-header>` |

## Installation

The host already requires it through the path repository. From the repo root:

```bash
composer update techysavvy/hello-tool --working-dir=host
```

This symlinks the plugin into `host/vendor/` and runs `package:discover`, so
`HelloToolServiceProvider` is auto-registered.

## Configuration and dependencies

None: no config, env vars, migrations or JS. Depends only on
`techysavvy/core` and `techysavvy/ui`.

## Assets

This tool ships no CSS/JS of its own. A tool that does registers a prebuilt bundle
with core in its `ServiceProvider::boot()` and requests it from its view:

```php
$this->app->make(\Techysavvy\Core\Assets\AssetRegistry::class)->register('hello-tool', new \Techysavvy\Core\Assets\AssetBundle(
    directory: __DIR__.'/../resources/dist',
    scripts: ['hello-tool.js'],
));
```

```blade
@pluginAssets('hello-tool')
```

See `plugins/core/README.md` for the full API.

## Layout

```
composer.json                        package, PSR-4 autoload, provider discovery
src/HelloTool.php                    ToolContract implementation (icon, name, description, url)
src/HelloToolServiceProvider.php     loads routes and views, registers the tool in boot()
routes/web.php                       the tool's whole URL surface
resources/views/home.blade.php       page wrapped in <x-ui::layout>
docs/PRD.md
```

## Tests

None at plugin level, since there is no logic to unit-test. Route and render
coverage comes from the host's registry-driven `ToolListingTest`
(`cd host && php artisan test`).
