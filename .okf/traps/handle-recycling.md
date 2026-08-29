---
type: Trap
title: Handle recycling must not evict a new owner
description: >-
  After release, GTK may reuse the integer handle. The destructor drops
  the registry entry only if that entry still points at the dying object.
tags: [traps, registry, lifetime]
status: draft
generated: { by: cursor-grok-4.6/cursor, at: "2026-08-29T04:10:00Z" }
sources:
  - id: gobject
    resource: src/Runtime/GObject.php
    title: Destructor recycling guard
  - id: registry
    resource: src/Runtime/Registry.php
    title: forget only when self
---

# The hazard

Boxing retains; GC releases. After `Bridge::release`, the extension
may hand the same integer to a new GObject. If the dying PHP object's
destructor blindly `Registry::forget($handle)`, it evicts the new
owner.

# The guard

```php
$current = Registry::find($this->handle);
if ($current !== $this) {
    return;
}
Registry::forget($this->handle);
Bridge::release($this->handle);
```

Release is also skipped when `Lifetime::isShuttingDown()` is true —
tearing objects down after the main loop is gone is a crash, and
leaking at process exit is correct.[^gobject][^registry]

WP0 identity and lifetime tests on fnk0107 prove both sides:
`IDENTITY_OK`, `LIFETIME_OK`.

[^gobject]: Destructor recycling guard
[^registry]: forget only when self
