# techysavvy

A Laravel monorepo: a thin bootstrapper app (`host/`) that discovers and hosts small, independent tools (`plugins/*`).

## Layout

```
techysavvy/
├── host/                     # the only runnable Laravel application
├── plugins/
│   ├── ui/                   # branding + generic Blade UI components
│   ├── core/                 # ToolContract interface + ToolRegistry
│   └── <tool-name>/          # one folder per tool
└── issues/prd.md             # spec for this bootstrap
```

Full rationale and decisions: [`issues/prd.md`](issues/prd.md).

## How it works

- Each `plugins/<name>/` folder is a standalone Composer package (own `composer.json`, own `ServiceProvider`), wired into `host/` via a Composer path repository (`../plugins/*`).
- A plugin registers itself by implementing `Techysavvy\Core\ToolContract` (`icon()`, `name()`, `description()`, `url()`) and, in its own `ServiceProvider::boot()`, pushing an instance of that class into the `ToolRegistry` singleton.
- `host/`'s home page reads `ToolRegistry::all()` and renders a card per tool — it never references a plugin's classes directly.
- Branding and generic UI (layout shell, tool grid, tool card) live in `plugins/ui` and are consumed by `host/` and every plugin the same way.

`plugins/hello-tool` is a working reference plugin — copy its shape when starting a new tool.

## Commands

See the [Makefile](Makefile) — all commands operate on `host/` under the hood.

```sh
make install   # composer install + npm install inside host/
make serve     # php artisan serve
make test      # php artisan test
```

## Adding a new plugin

1. Create `plugins/<name>/` with a `composer.json` (see `plugins/hello-tool/composer.json`), a `src/<Name>ServiceProvider.php`, and a class implementing `ToolContract`.
2. Add `"techysavvy/<name>": "*"` to `host/composer.json`'s `require`.
3. Run `make install` (or `composer update techysavvy/<name>` inside `host/`).
