---
name: plugin-review
description: Use when a techysavvy plugin branch is implemented and needs review before a PR or merge — including when tests and CI are green and the user wants it merged quickly.
---

# Plugin Review

## Overview

Check a finished plugin branch against its spec and the repo's architecture, fix what's found test-first, then finish: docs, spec status, PR.

**Core principle:** green tests prove only what they test. The review's job is to find what they don't — requirements without a proving test, and rules no test encodes.

Repo facts: `../plugin-dev/references/` (`review-checklist.md`, `testing.md`, `verify.md`).

## The review report

Produce this, in this order, before changing anything:

1. **Plan state** — plan `Status`, any unticked tasks, every `Blocked:` / `Deviation:` line.
2. **Requirement trace** — one row per `R` in the spec:
   | Req | Implemented at (file:line) | Proving test | Verdict |
   Verdict is `met` (code + a test that would fail without it), `untested` (code, no proving test), or `missing`. "Probably covered" is not a verdict — open the test file and find it.
3. **Architecture** — every row of `review-checklist.md`, with `pass` or the finding.
4. **Code quality** — only issues a reader would act on: dead code, duplicated logic, unclear names, missing error states from the spec.
5. **Findings** — numbered, each `Critical` (spec missing / plugin broken on fresh checkout / boundary rule broken) or `Minor`, with the fix.

Anything outside what the branch changed goes under "Not in scope" instead of Findings.

## Fixing

Show the report, then fix — with the user's go-ahead if they asked only for a review.
- `missing` / `untested` requirement → test first: write the proving test, run it, see it fail (or, for `untested`, see it pass and confirm it would fail without the code), then implement.
- Architecture findings → fix, and add a regression test where `testing.md` shows one (e.g. no inline `<script>`).
- A `Blocked:` question the user hasn't answered stays their decision; propose a default, don't assume it.
- Commit each fix separately: `fix(<kebab>): …`.

## Finish

Only when every finding is fixed or explicitly deferred by the user:
1. Run every command in `verify.md`, fresh; quote the results.
2. README: describes only built behavior; non-goals aren't claimed; deferred items marked Planned.
3. Spec `Status: Built`; plan `Status: Done`. Commit `docs(<kebab>): mark spec built`.
4. Push and open the PR against `main` (if the user asked for it; otherwise ask). Body: summary, the requirement trace table, verification output, deferred items.

## Red flags

| Thought | Reality |
|---|---|
| "CI is green, so it's done" | Check what CI actually runs for this plugin (Makefile/CI entries). |
| "The unit tests probably cover R3" | Find the test or mark it `untested`. |
| "Small fix, I'll just add the button" | Missing requirement → proving test first. |
| "Open the PR now, fix in follow-ups" | Only if the user defers the findings explicitly. |
