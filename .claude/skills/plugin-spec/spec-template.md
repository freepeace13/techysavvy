# <Name> — Spec<: Feature title, for a feature spec>

Status: Draft | Approved | Built
Plugin: `plugins/<kebab>` · Date: YYYY-MM-DD

## Problem & audience
Who needs this, what job it does, why a tool page.

## Scope
**In:** bullet list of what this spec delivers.
**Out (non-goals):** bullet list — each one is a future backlog candidate.

## Core flow
Numbered happy path from landing on the page to getting the result.

## Requirements
Numbered, each testable (`R1`, `R2`, …). `plugin-plan` maps every R to tasks; `plugin-review` traces every R to code + test.

## Data & privacy
What's uploaded, stored, logged, or sent to third parties; retention. "Client-side only, nothing leaves the browser" is an answer.

## Dependencies
Composer / npm packages (with size/licence notes if notable), PHP extensions, external services, env vars. "None".

## Routes & UI
Routes (`<kebab>.home` + any others), which `<x-ui::...>` components, whether it needs a plugin asset bundle.

## Persistence & config
Migrations/models or "stateless"; `config/<kebab>.php` keys.

## Decisions
| Decision | Choice | Why | Source |
|---|---|---|---|
| e.g. Processing location | Client-side | privacy; matches qr-forge | user / proposed |

## Success criteria
Observable checks that mean this spec is done.

## Open questions
Anything unresolved. Must be empty (or explicitly deferred) before `Approved`.
