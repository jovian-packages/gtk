---
type: Component
title: Runtime — GObject, Registry, Lifetime, Bridge
description: >-
  PHP refcount owns the handle. The Registry is a weak identity map.
  Lifetime::boot() is the only Bridge::init() caller.
resource: src/Runtime
tags: [runtime, gobject, lifetime, bridge]
status: draft
generated: { by: cursor-grok-4.6/cursor, at: "2026-08-29T04:10:00Z" }
sources:
  - id: gobject
    resource: src/Runtime/GObject.php
    title: Handle DTO base
  - id: registry
    resource: src/Runtime/Registry.php
    title: Weak identity map
  - id: lifetime
    resource: src/Runtime/Lifetime.php
    title: boot + shutdown flag
  - id: bridge
    resource: src/Runtime/Bridge.php
    title: 12-method projection of Gtk\Bridge\Bridge
---

# GObject

`Runtime\GObject` holds one `int $handle` and no other state.
`typeName()`, `isValid()`, `isA()`, `getProperty()`, and `setProperty()`
always ask the [Bridge](/runtime.md), so PHP and GObject cannot
disagree.[^gobject]

The PHP hierarchy mirrors GObject: `GObject` → `GtkWidget` →
`GtkWindow` → `GtkApplicationWindow`.

# Registry

`Runtime\Registry` maps `int $handle` → `WeakReference<GObject>`.
Boxing an already-boxed handle returns the identical instance, so a
signal sender resolves to the object you already hold.[^registry]

First-seen handles are classed by `Bridge::typeName()` through
`GeneratedTypeMap` (GType name → PHP class), then the nearest bound
ancestor, then `GObject`. Handle `0` is null.

Construction retains. Destruction releases and drops the registry entry
**only if that entry still points at the dying object** — see
[handle recycling](/traps/handle-recycling.md).

# Lifetime

`Lifetime::boot()` calls `Bridge::init()` once and is idempotent.
Nothing calls it implicitly. Constructing a DTO before boot throws
`NotBooted`.[^lifetime]

A shutdown function sets a flag after which destructors skip release.
Releasing after the GTK main loop is gone is a crash; leaking at
process exit is correct.

# Bridge

Twelve static methods, 1:1 with `Gtk\Bridge\Bridge`.[^bridge]
`useBackend()` is a Mac test seam so Pest can run without `gtk.so`.
Production leaves the backend null and calls the extension.

| Method | Role |
|---|---|
| `init()` | `gtk_init_check` once |
| `retain` / `release` / `isValid` | handle registry |
| `typeName` / `isA` / `typeFromName` | GType queries |
| `pump(timeoutMs)` | PHP-driven main loop |
| `connect` / `disconnect` | signals with return writeback |
| `getProperty` / `setProperty` | reserved properties |

`Bridge::retain` on ext-gtk is a validity check (the handle is still in
the GHashTable), not a GObject ref increment.
`phpgtk_bridge_release` removes that slot.

[^gobject]: Handle DTO base
[^registry]: Weak identity map
[^lifetime]: boot + shutdown flag
[^bridge]: 12-method projection of Gtk\Bridge\Bridge
