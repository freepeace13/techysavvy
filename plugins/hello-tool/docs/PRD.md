# Hello Tool — PRD

## Purpose
A minimal, always-working example of a tool plugin. New tools are scaffolded
by copying its shape, so it doubles as living documentation of the plugin
contract.

## Goals
- Demonstrate the full path: `composer.json` discovery, `ServiceProvider::boot()`
  registration with `ToolRegistry`, `ToolContract` implementation, routes,
  namespaced views and `<x-ui::...>` components.
- Stay tiny and dependency-free so it never breaks for unrelated reasons.

## Non-goals
- Any real functionality, persistence, config or JavaScript.
- Plugin-level tests (nothing to test beyond what the host seam test covers).

## Behavior
- A card titled "Hello Tool" (👋) appears on the host home page.
- The card links to `/hello-tool`, which renders a static page.

## Success criteria
- `php artisan route:list --name=hello-tool` shows `hello-tool.home`.
- The host's `ToolListingTest` passes with it installed and without it.

## Maintenance
Keep it in sync with the `create-plugin` skill: when the recommended plugin
shape changes (PHP version, component usage, file layout), update this
plugin in the same change.
