# EnvDiff — PRD

## Problem & audience

Developers and ops folks routinely let `.env` and `.env.example` drift, or
paste env files into chats and tickets with real secrets in them. Existing
tools are scattered (linters, diff tools) and many are server-side, which is a
non-starter for anyone pasting secrets. EnvDiff is a paste-and-go page that
never leaves the browser.

## Goals

- Show missing and extra keys between two pasted env files.
- Flag empty or placeholder values (`changeme`, `xxx`, `TODO`).
- Flag likely real secrets (known prefixes such as `sk_live_`, `AKIA…`; high-entropy values), especially in a file meant to be an example.
- Generate a clean `.env.example` (values stripped, comments and ordering preserved), copyable.
- State plainly why pasting here is safe, and make that claim verifiable.

## Non-goals (v1)

Accounts, saved sessions, share links, server-side parsing, multi-file or
multi-environment diffs, editing files in place, validating values against a schema.

## Core flow

1. User opens `/env-diff`.
2. Pastes file A (e.g. `.env`) and file B (e.g. `.env.example`).
3. Results appear live: missing/extra keys, placeholder and secret warnings.
4. User copies the generated `.env.example`.

## Functional requirements

- Parser handles comments, blank lines, `export` prefix, quoted values, inline comments, duplicate keys (report them).
- Diff is by key; values are never displayed in warnings beyond a masked preview.
- Secret heuristics are conservative and clearly labelled "likely".
- Works with no network after the page has loaded.

## Data & privacy

- Client-side only: no `fetch`, form POST, or beacon carries user input.
- No storage of input (server, cookies, `localStorage`, `sessionStorage`).
- No third-party scripts or analytics on this page.
- Safety note on the page lists these guarantees and tells users how to verify them (Network tab), plus a caution against pasting live production secrets on shared machines.

## Dependencies

None planned. Parsing and heuristics in plain JS, bundled through core's asset registry (`AssetRegistry::register()` + `@pluginAssets('env-diff')`), per the repo rules.

## Routes / UI outline

- `GET /env-diff` (`env-diff.home`): two `<x-ui::panel>` textareas, results panel, generated-example panel, the safety `<x-ui::alert>`.

## Open questions

- Which secret patterns ship in v1 (prefix list vs entropy threshold)?
- Should the safety note also be linked from the home-page card?

## Success criteria

- Diff, warnings and example generation work for typical Laravel/Node env files.
- DevTools shows zero requests triggered by pasting or diffing.
- Parser and heuristics have unit tests (JS test runner in the plugin).

## Milestones

1. Client-side parser + tests (comments, quotes, `export`, duplicates).
2. Key diff (missing/extra) and results UI.
3. Placeholder and empty-value detection.
4. Secret heuristics (prefixes, then entropy) with masked previews.
5. `.env.example` generator with copy button.
6. Bundle JS via `AssetRegistry`/`AssetBundle` and `@pluginAssets('env-diff')`; verify no network activity on paste.
7. Polish: empty states, mobile layout, accessibility pass.
