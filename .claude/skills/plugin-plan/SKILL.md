---
name: plugin-plan
description: Use when a techysavvy plugin or plugin feature has an approved spec (plugins/<name>/docs/SPEC.md or docs/specs/*.md) and needs an implementation plan before any code — including when the user says it's small or wants it executed right away.
---

# Plugin Plan

## Overview

Turn an approved spec into a plan file that an executor with zero context can follow task by task, each task test-first and independently verifiable.

**Core principle:** the plan decides *how*, never *what*. Every "what" lives in the spec, approved by the user. A gap found while planning goes back to the spec, not into the plan.

Repo facts: `../plugin-dev/references/` (`conventions.md`, `scaffold-recipe.md`, `testing.md`, `verify.md`).

## Preconditions

The spec file exists with `Status: Approved`. If not → `plugin-spec` first.

## Process

1. **Read** the spec, `conventions.md`, `testing.md`, and the closest precedent plugin (`qr-forge` for client-side JS tools, `hello-tool` for minimal).
2. **Gap check.** For every requirement, ask: could two competent executors build it differently in a way the user would notice (a limit, a default, an error message, an edge case, a filename)? Each such gap:
   - add a row to the spec's Decisions table with your recommended choice, Source `proposed`, set spec `Status: Draft`;
   - after the pass, show the user all new rows in one message and get approval (per `plugin-spec`'s gate); flip to `user`, `Status: Approved`, commit `docs(<kebab>): resolve spec gaps`.
   Write the plan only against an Approved spec. The plan has no "Decisions" section.
3. **Write** `plugins/<kebab>/docs/plans/YYYY-MM-DD-<slug>.md` from `plan-template.md`. New plugin → Task 0 is the scaffold (`scaffold-recipe.md`, verbatim steps + verify). Asset bundle, Makefile and CI lines are their own task, placed when JS is introduced.
4. **Coverage.** Every `R` in the spec appears in the coverage table with the task(s) and test(s) that prove it. A requirement with no test → add a task or a test.
5. **Hand off.** Commit `docs(<kebab>): add plan <slug>`. Show the user the path and the task list (one line each). Then:
   - user already asked for execution to follow → continue with `plugin-execute`;
   - otherwise → ask whether to execute now with `plugin-execute`.

## Task shape

Every task in the plan is exactly this shape (see `plan-template.md`):

- **Covers:** requirement ids
- **Files:** exact paths, created/modified
- **Steps**, each a checkbox, one action each, 2-5 minutes:
  1. Write the failing test — the test code itself
  2. Run it — exact command + the expected failure line
  3. Implement — the code, or precise instructions if it's markup
  4. Run it — exact command + expected pass
  5. Commit — exact message
- One behavior per task. More than ~3 tests or more than one module → split the task.

Scaffold, plumbing, and docs tasks replace steps 1-4 with the action + a verify command and its expected output.

**Proof task** (an end-to-end test over code earlier tasks built, e.g. an export round-trip): write the test, run → expected PASS, commit. Mark it `Proof task: expected to pass on first run; if it fails, stop and report — don't change code to make it pass.`

## Scope of the plan

The plan ends at "all of `verify.md` passes". Pushing, opening the PR, README and spec status belong to `plugin-review`.

## Red flags

| Thought | Do instead |
|---|---|
| "The spec doesn't say — I'll pick a sensible default and note it" | Spec gap → spec Decisions row, `proposed`, user approves. |
| "I'll list my assumptions for the user to review at the end" | By then it's built. Resolve before the plan is written. |
| "It's small, the plan can be light" | Fewer tasks, same shape. Each task still has its test, commands, expected output, commit. |
| "The executor is smart, it'll figure out the test" | Write the test code in the plan. |
