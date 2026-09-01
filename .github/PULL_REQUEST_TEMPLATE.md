## What does this change

<!-- Which tool(s) or area does this touch? What does it do? -->

## Checklist

- [ ] Tool-specific logic stays inside `plugins/<name>/` — nothing tool-specific was added to `host/`.
- [ ] No plugin reaches into another plugin's `src/` directly (only `ServiceProvider` and `ToolContract` are used as public surface).
- [ ] New tools register via `ToolRegistry::register()` inside their own `ServiceProvider::boot()`.
- [ ] Branding/UI uses `plugins/ui`'s `<x-brand::...>` components rather than duplicated markup/styles.
- [ ] Tests were added/updated in the correct location (`plugins/<name>/tests` for plugin behavior, `host/tests` for host-level seams).
- [ ] `make test` passes locally.
- [ ] `cd host && ./vendor/bin/pint` passes locally (or was run with no changes needed).

## Related issue

<!-- Link the issue this closes/relates to, if any -->
