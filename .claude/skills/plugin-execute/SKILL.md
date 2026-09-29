---
name: plugin-execute
description: Use when executing an implementation plan for a techysavvy plugin (plugins/<name>/docs/plans/*.md) — including when the user is away, the plan turns out wrong mid-task, or a needed ui component or API doesn't exist.
---

# Plugin Execute

## Overview

Carry out a plugin plan task by task, test-first, committing as you go — and route every departure from the plan by **where its fix would land**.

**Core principle:** the plan is the contract for scope. A fix inside the current task's files is yours to make; a fix anywhere else is a decision for the user.

Repo facts: `../plugin-dev/references/` (`conventions.md`, `testing.md`, `verify.md`).

## Setup

1. Read the whole plan, its spec, and `conventions.md`. Compare the plan's assumptions (file names, exports, component props) against the actual files before Task 1; list mismatches.
2. Branch: the one named in the plan header. It should already hold the spec + plan commits. If you're on any other branch, `git switch` to it; if it doesn't exist, create it from `main` and `git cherry-pick` the spec/plan commits. Never start from a branch carrying unrelated work. Uncommitted changes that aren't yours: leave them as they are (no stash, discard, or `git add -A`) and stage only named paths; if the switch refuses, stop and ask.
3. Fresh checkout? Run the prep block in `verify.md` first.
4. Set the plan's `Status: In progress`.

Execute inline by default. Use a subagent per task only if the user asks.

## Per task

Follow the task's steps exactly. After each step, tick its checkbox in the plan file. Commit with the plan's message, plan file included. A task is done when its expected PASS output appears — read it, don't assume it.

An expected-FAIL step must fail **for the stated reason**. A different error (import, typo, missing fixture) is a test-setup problem: fix it inside the test, rerun until the failure is the stated one.

## When reality disagrees with the plan

Route by where the fix lands:

| The fix would change… | Action |
|---|---|
| Only files the current task lists, without changing behavior the spec defines (test setup, wrong import name, wrong expected-output text) | Fix it. Add a `Deviation:` line under the task in the plan. Continue. |
| Behavior the spec defines, or a test's expected values (fixtures, assertions) | Stop the task. The spec decides; if it's silent → ask the user. Never edit an assertion or fixture to match your output. |
| Anything outside `plugins/<kebab>/` — `plugins/ui`, `plugins/core`, another plugin, `host/` — beyond what the plan lists (`host/composer.json`/lock, `Makefile`, CI) | Stop the task. Ask the user, with options. First option: the existing precedent that stays inside the plugin (e.g. qr-forge styles a plain `<select>` with a `$fieldClass` string of `ui` tokens rather than adding `<x-ui::select>`). |
| An environment failure (missing `.env`, unbuilt assets, `ViteManifestNotFoundException`) | Run `verify.md` prep, rerun. Still failing → check whether `main` fails too; report either way. |

**User away?** A stopped task is recorded, not improvised: write `Blocked:` + the question under it in the plan, then continue only with later tasks that don't depend on it. When nothing independent remains, stop and report.

## Finish

1. Every checkbox ticked (or `Blocked:` with a question).
2. Run every command in `verify.md` for this plugin, fresh. Quote the result lines in your report.
3. Plan `Status: Done` (or leave `In progress` if anything is blocked); commit.
4. Report: branch, commits, verification output, each `Deviation:` and `Blocked:` item. Don't push or open a PR — next phase is `plugin-review`.

## Red flags

| Thought | Reality |
|---|---|
| "It's a small additive change to ui/core, I'll flag it" | Every tool depends on it. Outside the plugin → ask first. |
| "CLAUDE.md says use ui components, so I must add one" | Plain elements styled with `ui` tokens are allowed; duplicating a component's markup isn't. Check qr-forge's precedent. |
| "The fixture is probably wrong" | The spec decides, not your output. |
| "User's away, I'll make the call and they can revert" | Record `Blocked:`, do independent tasks, stop. |
| "Tests should pass now" | Run them. Quote the output. |
