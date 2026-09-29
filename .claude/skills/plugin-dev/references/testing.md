# Testing

Tests belong with what they test.

| Tier | Where | Harness | Use for |
|---|---|---|---|
| Host seam | `host/tests/Feature/` | host `Tests\TestCase` | Cross-plugin behavior only. `ToolListingTest` is registry-driven — never add a specific tool to it. |
| Plugin feature | `plugins/<kebab>/tests/Feature/` | plugin `Tests\TestCase` extending `Orchestra\Testbench\TestCase` (copy qr-forge's) | Routes respond, views render, registry registration, asset bundle requested, form/endpoint behavior. |
| Plugin unit | `plugins/<kebab>/tests/Unit/` | plain PHPUnit / same TestCase | Pure PHP logic (converters, calculators, validators). |
| Plugin JS | `plugins/<kebab>/tests/js/*.test.js` | `node --test` (no extra deps) | Client logic in `resources/js/` — keep logic in importable modules so it's testable without a browser. |

Rules:
- A plugin test never extends or imports host's `Tests\TestCase` — a plugin must not depend on `host/`.
- A host test never asserts a specific plugin's name/description/route.
- Test behavior through the seam (HTTP request → response), not by reflecting into the container.
- Asset-bundle tests skip (not fail) when `resources/dist/` isn't built — see qr-forge's `test_home_page_requests_the_registered_bundle`.
- Pages that use assets assert no inline `<script>`/`<style>` blocks (qr-forge's `test_home_page_has_no_inline_script_or_style_block`).
