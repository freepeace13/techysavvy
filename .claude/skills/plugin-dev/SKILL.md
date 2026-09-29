---
name: plugin-dev
description: Use when creating a new tool/plugin under plugins/ in the techysavvy monorepo, adding a feature to an existing plugin, or resuming plugin work in progress — to find which phase (spec, plan, execute, review) comes next.
---

# Plugin Dev

Plugin work in this repo moves through four phases, each its own skill, each leaving a file in the plugin that the next one reads:

```
plugin-spec  →  plugin-plan  →  plugin-execute  →  plugin-review
 docs/SPEC.md    docs/plans/*.md   code + ticked plan   fixes, README, PR
```

Repo facts all phases share: `references/` (conventions, scaffold recipe, testing, verify, review checklist).

## Where to start

Find the spec that covers the request, then read its and its plan's `Status:` lines:

| On disk | Next |
|---|---|
| No `plugins/<kebab>/`, or no spec for this feature | `plugin-spec` |
| Spec `Status: Draft` | `plugin-spec` (finish approval) |
| Spec `Approved`, no plan for it | `plugin-plan` |
| Plan `Ready` or `In progress` (unticked or `Blocked:` tasks) | `plugin-execute` |
| Plan `Done`, spec not `Built` | `plugin-review` |
| Spec `Built`, user wants something new | `plugin-spec` — a new feature spec in `docs/specs/` |

Older plugins have `docs/PRD.md` instead of `docs/SPEC.md`: treat it as a `Built` spec for v1, so new work starts a feature spec.

Tell the user which phase you're entering and why (one line), then invoke that skill. Don't skip a phase because the request sounds small — each skill handles the small case itself.
