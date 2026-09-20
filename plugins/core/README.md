# techysavvy/core

Shared plumbing every tool plugin and the host depend on: the tool registry and
the plugin asset registry. Contains no tool-specific logic.

## Tool registry

`ToolContract` (`icon()`, `name()`, `description()`, `url()`) and `ToolRegistry`
(`register()`, `all()`), bound as a singleton in `CoreServiceProvider::register()`.
A tool registers itself from its own `ServiceProvider::boot()`; the host lists
whatever is registered.

## Plugin assets

Plugins build their own CSS/JS and declare the result; core serves and emits it.

| Piece | Role |
|-------|------|
| `Assets\AssetBundle` | Immutable, validated description of a plugin's dist directory plus its declared `scripts` / `styles` |
| `Assets\AssetRegistry` | Singleton, boot-time map of bundle name → `AssetBundle`; `url()` builds fingerprinted URLs |
| `Assets\RequiredAssets` | Container-**scoped**, per-request set of bundles the page asked for (separate from the registry so worker runtimes don't drop registrations) |
| `Assets\AssetRenderer` | Turns the required set into `<link>` / `<script>` markup |
| `GET /_plugin-assets/{bundle}/{file}` (`core.assets`) | Serves declared files only, with `ETag` (304 support) and `Cache-Control: public, max-age=31536000, immutable` |
| `@pluginAssets('name')` | Blade directive: mark a bundle as required for this page |
| `<x-core::assets.styles />`, `<x-core::assets.scripts />` | Emit points; `plugins/ui`'s layout places them in `<head>` and before `</body>` |

### Declaring a bundle

```php
use Techysavvy\Core\Assets\AssetBundle;
use Techysavvy\Core\Assets\AssetRegistry;

// in the plugin's ServiceProvider::boot()
$this->app->make(AssetRegistry::class)->register('my-tool', new AssetBundle(
    directory: __DIR__.'/../resources/dist',
    scripts: ['my-tool.js', 'boot.js' => ['module' => true]],
    styles: ['my-tool.css'],
));
```

```blade
{{-- in the tool's view, anywhere before or inside the layout --}}
@pluginAssets('my-tool')
```

### Rules and behaviour

- Bundle names match `[a-z0-9-]+`. File names are plain (`^[A-Za-z0-9][A-Za-z0-9._-]*$`,
  no `..`, no path separators) and end in `.js` or `.css`; anything else throws.
- Only declared files are ever resolved, so the route cannot address any other file.
- Generated URLs carry `?v=<12-char content hash>`, so the immutable cache header is
  safe: a rebuild changes the URL. There is no publish step.
- Classic scripts are emitted as plain `<script src>` (deliberately **not** `defer`):
  the layout's Alpine starts from a deferred module in `<head>`, and a deferred
  plugin script would run after it. Use `['file.js' => ['module' => true]]` for
  an ES module.
- A declared file that is missing on disk (not built yet) is skipped and logged;
  with `APP_DEBUG=true` an HTML comment names it. The page still renders.
- Requiring an unknown bundle throws `InvalidArgumentException`; registering a
  name twice throws `LogicException`.

## Tests

```sh
composer install --working-dir=plugins/core
cd plugins/core && vendor/bin/phpunit
```
