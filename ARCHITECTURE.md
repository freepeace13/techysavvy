# Architecture

This document explains *why* techysavvy is shaped the way it is, for anyone
contributing a new tool or working on the host bootstrapper. For the
contribution workflow itself, see [`CONTRIBUTING.md`](CONTRIBUTING.md); for
the concrete rules PRs are reviewed against, see [`CLAUDE.md`](CLAUDE.md).

## Problem

Instead of one monolithic Laravel app, techysavvy is built as a **monorepo
of small, focused "tools" (plugins)** that ship together but stay cleanly
separated in code. Without an established structure, every new tool risks
becoming entangled with the main app — hard to isolate, hard to reason about
independently, and hard to extract or reuse. Tools also need to consistently
look and feel like part of the same product (shared branding, shared UI
primitives) without duplicating that work in every plugin.

## Solution

A fresh Laravel application lives isolated in its own root-level `host/`
folder and acts purely as a **bootstrapper/host**: it wires up routing,
shared services, and a navigation surface, but contains none of the
tool-specific logic itself. Each tool lives in its own folder under
`plugins/` as a **real, standalone Composer package** (own `composer.json`,
own namespace, own `ServiceProvider`), wired into the host app via Composer
path repositories. Plugins register and boot themselves through their own
`ServiceProvider` — the host app never reaches into a plugin's internals, it
only requires the package and lets Laravel's provider/package-discovery
mechanism do the rest.

Two dedicated shared packages make this work:

- **`plugins/ui`** provides branding assets and generic UI components
  (Blade components, design tokens/styles) that both the host app and every
  plugin depend on, so visual consistency is enforced by dependency, not
  convention.
- **`plugins/core`** exposes a `ToolContract` interface and a
  `ToolRegistry`: each plugin implements the contract to describe itself
  (icon, name, description, url) and registers that description into the
  registry during boot, so the host app can discover installed tools and
  render a tool-listing grid without knowing anything about any individual
  plugin.

## Repo shape

```
techysavvy/
├── CLAUDE.md
├── README.md
├── ARCHITECTURE.md
├── Makefile
├── host/                     # the bootstrapper Laravel app, fully isolated
│   ├── app/ bootstrap/ config/ database/ public/ resources/ routes/ storage/ tests/
│   ├── artisan
│   ├── composer.json         # requires plugins/* via path repos (../plugins/*)
│   └── package.json
└── plugins/
    ├── ui/                    # branding + generic UI components
    │   ├── src/UiServiceProvider.php
    │   ├── resources/{views/components/, css|assets/}
    │   └── composer.json
    ├── core/                  # ToolContract interface + ToolRegistry
    │   ├── src/
    │   │   ├── ToolContract.php
    │   │   ├── ToolRegistry.php
    │   │   └── CoreServiceProvider.php
    │   └── composer.json
    └── <tool-name>/
        ├── src/
        │   ├── <ToolName>ServiceProvider.php
        │   └── <ToolName>Tool.php    # implements ToolContract
        ├── routes/web.php
        ├── resources/views/
        ├── config/
        ├── database/migrations/
        ├── tests/
        └── composer.json
```

`host/` is the only runnable Laravel application; nothing under `plugins/`
is bootable on its own.

## Key decisions

- **Plugin packaging** — each `plugins/<plugin-name>/` folder is a
  self-contained Composer package with its own `composer.json`, `src/`,
  `routes/`, `resources/views/`, `config/`, and `database/migrations/` as
  needed. A plugin's dependencies are declared in its *own* `composer.json`,
  not the host's, so a plugin's dependency footprint is self-documenting.
- **Wiring mechanism** — `host/composer.json` declares a Composer **path
  repository** pointing at `../plugins/*` and `require`s each plugin package
  by name. `composer install`/`update`, run with `host/` as the working
  directory, resolves these as symlinks into `host/vendor/`, so plugin code
  is loaded through the normal Composer autoloader. The root `Makefile`
  wraps day-to-day commands so `--working-dir` doesn't need typing out.
- **Registration/boot** — each plugin ships one `ServiceProvider` that
  registers its routes, views namespace, config, and migrations, declared
  via Composer's `extra.laravel.providers` so Laravel's package
  auto-discovery registers it automatically when the package is required.
  `host/config/app.php` is never edited to add a plugin.
- **Host role** — `host/` owns only cross-cutting host concerns: the base
  layout/shell, auth (if shared across tools), global middleware, and a
  navigation surface that lists installed plugins. It contains no
  tool-specific controllers, views, or migrations.
- **`ToolContract` / `ToolRegistry`** — `ToolContract` declares `icon():
  string`, `name(): string`, `description(): string`, and `url(): string`.
  `ToolRegistry` is a plain in-memory registry (`register()`, `all()`) bound
  as a singleton by `core`'s `CoreServiceProvider::register()` — binding it
  in `register()` rather than `boot()` guarantees it's available before any
  tool plugin's `boot()` tries to register into it, regardless of provider
  load order.
- **Host consumption** — `host/` depends on `core` (to read) and `ui` (to
  render), but not on any individual tool plugin. The home page resolves
  `ToolRegistry::all()` and renders a grid of cards, one per registered
  tool, each linking to its `url()`. `host/` never imports or references a
  specific plugin's classes — only the `ToolContract` type.
- **View/asset namespacing** — each plugin registers its Blade views under
  its own namespace (e.g. `<plugin>::`); `ui`'s components are registered
  under one stable namespace (`brand::`) so both host and plugin views
  reference them identically.

## Testing philosophy

- Tests exercise **behavior through the seam** — a real HTTP request into a
  plugin's route, through the fully-booted `host/` app — rather than
  asserting on internal provider wiring or reflecting into the container to
  check bindings.
- Each plugin owns its own feature tests, colocated with the plugin
  (`plugins/<plugin-name>/tests/`), runnable in isolation or as part of the
  full suite (run from `host/`, which is where the booted application and
  test runner live).
- A host-level feature test hits the home page and asserts a registered
  tool's name, description, and a link with `href` equal to its `url()` are
  present — proving the full path (plugin boots → registers into
  `ToolRegistry` → host reads registry → renders card → link is correct)
  end-to-end, rather than unit-testing `ToolRegistry` in isolation.
- Composer/package-resolution correctness (path repositories resolving,
  symlinks present, autoloader up to date) is a build-time concern verified
  by `composer install` succeeding — not encoded as a PHP test.

## Explicitly out of scope

- Any specific tool/plugin's business logic — this document covers only the
  bootstrapper/plugin-wiring architecture, not what any individual tool
  does.
- A single authentication/authorization strategy across all plugins.
- Publishing plugin packages to a public or private Composer registry —
  path repositories are strictly for in-monorepo development.
- Database-per-plugin vs. shared-database strategy — decided per-plugin as
  needed.
- Ordering, categorization, grouping, or per-user visibility of tools in the
  home page grid — the registry is a flat, unordered collection.
- Icon format/storage — `ToolContract::icon()` returns a `string`; what it
  represents (emoji, SVG, asset path, icon-font class) is a per-plugin
  choice, documented alongside `ui`'s card component expectations.
