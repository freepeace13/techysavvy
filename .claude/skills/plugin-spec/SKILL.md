---
name: plugin-spec
description: Use when turning an idea into a new tool/plugin under plugins/ in the techysavvy monorepo, or when adding a feature to an existing plugin — before any scaffolding, code, or plan. Also applies when the user says it's small, urgent, or that they already know what they want.
---

# Plugin Spec

## Overview

Turn an idea into an approved spec **file** before anything else touches `plugins/`. The file is the deliverable of this phase; a chat summary is not.

**Core principle:** the spec is written first and approved by the user as a file. Writing it after the code turns it into a changelog, and the decisions in it were never actually made with the user.

Repo facts (names, UI components, assets, doc paths): `../plugin-dev/references/conventions.md`.

## Output

| Situation | File |
|---|---|
| New plugin | `plugins/<kebab>/docs/SPEC.md` (create only the `docs/` folder — nothing else in the plugin yet) |
| Feature on existing plugin | `plugins/<kebab>/docs/specs/YYYY-MM-DD-<feature>.md` |

Fill `spec-template.md` (this folder). Every section present; write "None" rather than dropping one.

## Process

1. **Context.** Read `conventions.md`. Existing plugin: read its README and `docs/SPEC.md` (or `docs/PRD.md`); note anything the new feature contradicts (e.g. it's listed as a non-goal). New plugin: `ls plugins/` for collisions and the closest precedent.
2. **Name** (new plugin, no name given). Brainstorm ~8-10 privately; offer your top 3 (short, fancy yet sensible, hints at the job) with one-line rationale, favorite marked recommended, via `AskUserQuestion`.
3. **Shape.** One topic per round, 1-4 questions, multiple choice, recommendation first. Topics are the template's sections; skip what's answered.
   - **User in a hurry / "I know what I want":** collapse to ONE round: name choices + your proposed defaults for every open decision. Fewer rounds — not fewer sections, and not no file.
4. **Write the file** with `Status: Draft`. Every decision goes in the Decisions table with its source: `user` (they chose it) or `proposed` (your default).
5. **Approval gate.** Before the first commit, `git switch -c feat/<kebab>` (new plugin) or `feat/<kebab>-<feature>` from `main` — spec, plan and code all live on this branch. Show the path and a ≤10-line summary, listing every `proposed` decision explicitly. Ask for approval of the file. On approval: set `Status: Approved`, flip confirmed rows to `user`, commit (`docs(<kebab>): add spec` / `docs(<kebab>): spec <feature>`).
6. **Hand off.** Next phase is `plugin-plan`. Don't scaffold or implement from here.

## The gate

No scaffold, no `src/`, no `resources/`, no `composer.json`, no test, no plan until the spec file exists with `Status: Approved`.

Approval is of content the user actually saw:
- **Your message listed every decision (name + each proposed default) and the user said "go"/"yes"** → those decisions are approved. Write the file with them as `user`, `Status: Approved`, commit, and tell the user the path. No second confirmation.
- **The user said "go" before seeing the decisions** (e.g. "just build it" up front) → nothing is approved. Write the Draft and show it (one message, no new questions).
- **The file contains a decision that wasn't in the message they approved** → that row stays `proposed`; the file is Draft until they confirm it.

| Rationalization | Reality |
|---|---|
| "It's a small addition" | Small features still carry decisions (defaults, formats, naming). One short spec file takes minutes. |
| "They know what they want" | Then approval is one reply. Their knowledge still has to be written down to be checked. |
| "Demo in an hour" | Compact round + short spec. The file is not the slow part; rework from an unagreed default is. |
| "The playback in chat is the spec" | Chat isn't reviewable later and isn't what `plugin-plan` reads. Write the file — before any code, even when the chat "go" already approved it. |
| "They said go, so I'll fill in the rest myself" | A "go" approves only what was listed. New decisions are `proposed`. |
| "I'll update the PRD/spec at the end" | That's documentation of what you built, not a spec. Spec first. |
| "I'll state my defaults; they can object" | Silence isn't approval. List them as `proposed` and get an explicit yes. |

## Red flags — stop

- Creating anything under `plugins/<kebab>/` besides `docs/` before approval
- A spec step that comes after code in your own plan
- Decisions in your head or your message that aren't in the Decisions table
- "Unless you tell me otherwise…"
