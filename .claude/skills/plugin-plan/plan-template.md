# <Name> — Plan: <slug>

Status: Ready | In progress | Done
Spec: `docs/SPEC.md` or `docs/specs/<file>.md` (Approved) · Branch: `feat/<kebab>[-<slug>]` off `main`

**For the executor:** read the spec, `CLAUDE.md`, and `.claude/skills/plugin-dev/references/`. If reality disagrees with this plan, or the spec is ambiguous, stop and ask — don't improvise. Tick each checkbox as you go.

## Approach
3-5 sentences: where the code goes, what it reuses, what stays untouched.

## Coverage
| Req | Task(s) | Proving test(s) |
|---|---|---|
| R1 | 2 | `tests/js/convert.test.js` › "detects semicolon" |

## Task 0 — Scaffold  *(new plugin only)*
Covers: —
- [ ] Steps 1-9 of `scaffold-recipe.md` with names: `<kebab>` / `<Studly>` / `<Name>` / `<icon>`
- [ ] `cd host && php artisan route:list --name=<kebab>` → shows `<kebab>.home`
- [ ] `cd plugins/<kebab> && vendor/bin/phpunit` → `OK (2 tests`
- [ ] Commit `feat(plugins): scaffold <kebab> tool plugin`

## Task N — <one behavior>
Covers: R?
Files: create `path`, modify `path`
- [ ] Write the failing test in `path`:
  ```
  <test code>
  ```
- [ ] Run `<command>` → FAIL: `<expected message>`
- [ ] Implement in `path`:
  ```
  <code>
  ```
- [ ] Run `<command>` → PASS
- [ ] Commit `<type>(<kebab>): <message>`

## Final verification
- [ ] Every command in `verify.md` for `<kebab>`, output read → all pass
