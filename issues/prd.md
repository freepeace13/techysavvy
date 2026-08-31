# Laravel Plugin Monorepo Bootstrap

## Problem Statement

We're starting a new Laravel project from scratch, but instead of one monolithic app, we want to build it as a **monorepo of small, focused "tools"** (plugins) that all live and ship together, while still being cleanly separated in code. Today there is no scaffolding for this at all — the working directory is empty. Without an established structure, every new tool we build risks becoming entangled with the main app, making tools hard to isolate, hard to reason about independently, and hard to eventually extract or reuse. We also don't yet have a way for these tools to consistently look and feel like part of the same product (shared branding, shared UI primitives) without duplicating that work in every tool.

## Solution

Stand up a fresh Laravel application, isolated in its own root-level `host/` folder, that acts purely as a **bootstrapper/host**: it wires up routing, shared services, and a navigation surface, but contains none of the tool-specific logic itself. Each tool lives in its own isolated folder under a top-level `plugins/` directory as a **real, standalone Composer package** (own `composer.json`, own namespace, own `ServiceProvider`), wired into the main app via Composer path repositories. Plugins register and boot themselves through their own `ServiceProvider` — the main app never reaches into a plugin's internals, it only requires the package and lets Laravel's provider/package-discovery mechanism do the rest. A dedicated **shared package** provides branding assets and generic UI components (Blade components, design tokens/styles) that both the main app and every plugin depend on, so visual consistency is enforced by dependency, not convention. A second dedicated **shared package** exposes a `ToolContract` interface and a `ToolRegistry`: each plugin implements the contract to describe itself (icon, name, description, url) and registers that description into the registry during boot, so the host app can discover installed tools and render a tool-listing grid without knowing anything about any individual plugin.

## User Stories

1. As a developer, I want the main Laravel application isolated in its own root-level folder (not sprawled across the repo root), so that repo-root stays reserved for meta-files (`README.md`, `CLAUDE.md`, `Makefile`) and the app itself contains no tool-specific business logic.
2. As a developer, I want a top-level `plugins/` directory where each subfolder is one isolated tool, so that tools never accidentally share state or files with each other.
3. As a developer, I want each plugin to be a real Composer package with its own `composer.json`, so that it has its own explicit dependency list and namespace boundary.
4. As a developer, I want each plugin wired into the host app via a Composer path repository, so that I can develop plugins in-repo without publishing them to a registry.
5. As a developer, I want `composer install` inside the host app to resolve and symlink every plugin package into its `vendor/`, so that plugin code is autoloaded exactly like any third-party package.
6. As a developer, I want each plugin to register and boot exclusively through its own `ServiceProvider`, so that a plugin is self-contained and removable by deleting its folder and dropping it from `composer.json`.
7. As a developer, I want plugin `ServiceProvider`s to be auto-discovered by Laravel (via Composer package auto-discovery) rather than manually registered in the main app's `config/app.php`, so that adding a new plugin doesn't require editing host-app config.
8. As a developer, I want each plugin to be able to register its own routes, so that a tool's URL surface lives inside the tool's own folder.
9. As a developer, I want each plugin to be able to register its own views, so that a tool's Blade templates live inside the tool's own folder.
10. As a developer, I want each plugin to be able to register its own config, so that tool-specific configuration doesn't leak into the main app's `config/` directory.
11. As a developer, I want each plugin to be able to register its own migrations, so that a tool's database schema is versioned alongside the tool's code.
12. As a developer, I want a single shared package that holds branding assets (logo, colors, fonts) and generic UI components (buttons, cards, layout shells, nav components), so that every plugin and the main app render with a consistent look without copy-pasting UI code.
13. As a developer, I want the main app to depend on the shared UI package the same way a plugin does, so that the host app and every tool draw from one source of visual truth.
14. As a developer building a new plugin, I want a documented/scaffolded folder structure to copy, so that starting a new tool doesn't require re-deriving the wiring pattern each time.
15. As a developer, I want the main app to expose a way to see which plugins are currently installed/booted, so that I can verify at runtime that a plugin loaded correctly.
16. As a developer, I want to be able to add a brand-new plugin by creating a folder under `plugins/`, adding a path repository entry in the host app, and running `composer require`, so that plugin installation follows a single, repeatable recipe.
17. As a developer, I want to be able to remove a plugin cleanly by removing its `composer.json` entry and folder, so that decommissioning a tool doesn't leave orphaned registrations elsewhere in the app.
18. As a developer, I want each plugin's tests to run independently of other plugins' tests, so that a failure in one tool doesn't obscure failures in another.
19. As a developer, I want confidence that a plugin genuinely boots end-to-end (route reachable, view rendered, shared UI component resolved) rather than just being autoloadable, so that "installed" actually means "working."
20. As a developer, I want a shared contract package defining what every tool must expose (icon, name, description, url), so that any plugin implementing it is guaranteed to render correctly in the host's tool listing.
21. As a developer, I want each plugin to register an instance of its contract implementation into a shared `ToolRegistry` during its own boot, so that the host app discovers installed tools by reading the registry rather than maintaining its own list.
22. As an end user, I want to land on a home page (or equivalent tools page) in the main app that shows every installed tool as a grid of cards, so that I can see everything available to me at a glance.
23. As an end user, I want each tool's card to show its icon, name, and description, so that I can tell what a tool does before opening it.
24. As an end user, I want clicking a tool's card to take me to that tool's own registered route (its `url()`), so that navigating into a tool is a single click from the home page.
25. As a developer, I want a plugin's presence in the tool-listing grid to be entirely driven by whether it's installed and registers itself, so that removing a plugin's folder/`composer.json` entry automatically removes its card with no leftover reference in the host.

## Implementation Decisions

- **Repo shape**: single Git repository (monorepo) with three top-level entries: `host/`, `plugins/`, and repo-root meta-files (`README.md`, `CLAUDE.md`, `Makefile`, `.gitignore`, `issues/`). Nothing app-specific lives loose at repo root.
  ```
  techysavvy/
  ├── CLAUDE.md
  ├── README.md
  ├── Makefile
  ├── issues/
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
  `host/` is the only runnable Laravel application; nothing under `plugins/` is bootable on its own.
- **Plugin packaging**: each `plugins/<plugin-name>/` folder is a self-contained Composer package — its own `composer.json` (name, PSR-4 autoload for its namespace, its own `require` list), its own `src/`, `routes/`, `resources/views/`, `config/`, and `database/migrations/` as needed. A plugin's dependencies are declared in its *own* `composer.json`, not the host's, so a plugin's dependency footprint is self-documenting.
- **Wiring mechanism**: `host/composer.json` declares a Composer **path repository** pointing at `../plugins/*` (or one explicit relative path entry per plugin) and `require`s each plugin package by name. `composer install`/`update`, run with `host/` as the working directory, resolves these as symlinks into `host/vendor/`, so plugin code is loaded through the normal Composer autoloader — no custom autoloading or manual `require` statements. A root-level `Makefile` wraps these so day-to-day commands don't need `--working-dir` typed out (e.g. `make install`, `make serve`, `make test` shell out into `host/`).
- **Registration/boot**: each plugin ships one `ServiceProvider` that registers its routes, views namespace, config, and migrations, and performs any boot-time wiring (event listeners, gates, etc.). This provider is declared via Composer's `extra.laravel.providers` so Laravel's package auto-discovery registers it automatically when the package is required — `host/config/app.php` is never edited to add a plugin.
- **Main app role**: `host/` owns only cross-cutting host concerns — the base layout/shell, auth (if shared across tools), global middleware, and a navigation surface that lists installed plugins. It contains no tool-specific controllers, views, or migrations.
- **Shared branding/UI package**: `plugins/ui`, wired the same path-repository way as any tool plugin, providing Blade components, a base layout partial, and brand assets (colors, logo, typography). Both `host/` and every tool plugin `require` this package the same way they'd require any dependency — there is no "the main app publishes, plugins consume" special-casing. It's a peer of tool plugins under `plugins/`, not a separate top-level directory — same wiring mechanism, just conventionally first/special by name.
- **Tool contract package**: `plugins/core`, a peer of `ui` under `plugins/`, wired the same path-repository way. It exposes:
  - `ToolContract` — an interface every tool implements, declaring `icon(): string`, `name(): string`, `description(): string`, and `url(): string`. `url()` returns the tool's own registered route (e.g. `route('<tool>.home')`), so the contract implementation is the single place a plugin declares "this is my entry point."
  - `ToolRegistry` — a plain in-memory registry with `register(ToolContract $tool): void` and `all(): Collection` (or array), bound as a **singleton** in the container by `core`'s own `CoreServiceProvider`.
- **Registration flow**: each plugin ships one class implementing `ToolContract` (e.g. `<ToolName>Tool`) describing itself. In its own `ServiceProvider::boot()`, the plugin resolves `ToolRegistry` from the container and calls `register(new <ToolName>Tool())`. There is no central list maintained in `host/` — a plugin declares itself, and the registry simply accumulates whatever booted. Because Laravel runs every discovered provider's `register()` phase before any provider's `boot()` phase, binding the `ToolRegistry` singleton in `core`'s `register()` guarantees it's available by the time any tool plugin's `boot()` tries to register into it, regardless of provider load order.
- **Host consumption / tool listing**: `host/` depends on `core` (to read) and `ui` (to render), but not on any individual tool plugin. A route/controller (e.g. the home page) resolves `ToolRegistry::all()` and passes it to a Blade view that renders a **grid of cards**, one per registered tool, each showing `icon()`, `name()`, and `description()` and wrapping the card in a link to `url()`. The grid itself is a `ui` component so its look is consistent with the rest of the host's chrome. `host/` never imports or references a specific plugin's classes — it only ever depends on the `ToolContract` type.
- **View/asset namespacing**: each plugin registers its Blade views under its own view namespace (e.g. `<plugin>::`) to avoid collisions; `ui`'s components are registered under one stable namespace (e.g. `brand::` or an anonymous component namespace) so both host and plugin views can reference them identically.
- **New-plugin scaffolding**: the spec calls for a documented folder/file skeleton for a plugin (composer.json shape, provider shape, directory layout, matching the tree above) so future tools are created by copying a known-good shape rather than re-deriving it. Whether this becomes a `composer create-project`-style stub, an Artisan command, or just a documented template folder is left as an implementation detail for the build phase, not fixed here.

## Testing Decisions

- Tests should exercise **behavior through the seam identified above** — a real HTTP request into a plugin's route, through the fully-booted `host/` app — rather than asserting on internal provider wiring or reflecting into the container to check bindings.
- Each plugin owns its own feature tests, colocated with the plugin (`plugins/<plugin-name>/tests/`), and those tests should be runnable in isolation as well as as part of the full suite (run from `host/`, which is where the booted application and its test runner live).
- At least one test should assert the shared-UI package's components render correctly *from within a plugin's view*, proving the shared dependency is actually wired, not just present in `composer.json`.
- A host-level feature test should hit the home page and assert a registered tool's name, description, and a link with `href` equal to its `url()` are present in the response — proving the full path (plugin boots → registers into `ToolRegistry` → host reads registry → renders card → link is correct) end-to-end, rather than unit-testing `ToolRegistry::register()`/`all()` in isolation.
- Composer/package-resolution correctness (path repositories resolving, symlinks present, autoloader up to date) is a build-time concern verified by `composer install` succeeding — this is not something to encode as a PHP test.
- No prior art exists in this repo yet (greenfield); the convention to establish is: framework-level feature tests (Pest, matching current Laravel starter-kit defaults) at the HTTP boundary, one test file per plugin route/feature.

## Out of Scope

- Any specific tool/plugin's business logic — this spec covers only the bootstrapper/plugin-wiring scaffolding, not what any individual tool actually does.
- Authentication/authorization strategy details (whether auth is shared across all plugins, per-plugin, or absent) beyond noting the main app is the natural place to host it if shared.
- Deployment/CI pipeline changes.
- Publishing plugin packages to a public or private Composer registry — path repositories are strictly for in-monorepo development.
- Front-end build tooling specifics (Vite config, CSS framework choice) beyond the requirement that the shared-UI package is the single source of branding.
- Database-per-plugin vs. shared-database strategy — not raised in the request, so not decided here; will need its own decision when the first plugin with persistence is built.
- Ordering, categorization, or grouping of tools in the grid — the registry is a flat, unordered collection; sort/group behavior isn't specified and is left to whoever builds the listing view.
- Per-user or per-role visibility of tools in the grid (e.g. hiding a tool a user isn't authorized for) — not raised in the request; today every registered tool is assumed visible to every user who reaches the home page.
- Icon format/storage (emoji, SVG string, asset path, icon-font class) — `ToolContract::icon()` returns a `string`; what that string represents is left to the build phase and should be documented once decided, likely alongside the `ui` card component's expectations.

## Further Notes

- This is a greenfield repo (no Git history, no existing code, no issue tracker configured for this project yet) — this spec was written to a local file (`issues/prd.md`) rather than published to a tracker, per the user's choice during scoping. If/when an issue tracker is set up (`/setup-matt-pocock-skills`), this file's content should be filed there and labeled `ready-for-agent`.
- The structural decisions confirmed with the user before writing this spec: (1) plugins are wired via Composer **path repositories** as real standalone packages, not a plain extra-PSR-4-autoload-entry approach; (2) shared branding/UI lives in its **own dependency package** (`plugins/ui`), not published-by-main-app-and-consumed; (3) the main app is isolated into its own root-level `host/` folder rather than living at repo root, so repo root is reserved for `README.md`, `CLAUDE.md`, `Makefile`, and `plugins/`; (4) tool discovery/listing goes through a **shared contract + registry package** (`plugins/core`) rather than a hardcoded list in `host/` — each plugin self-describes via `ToolContract` and self-registers into `ToolRegistry` at boot.
