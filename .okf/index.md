---
okf_version: "0.2"
---

# jovian/gtk — knowledge bundle

Typed projection of `ext-gtk` ^0.8.0. Read this index first, then open
only the concepts the task needs. Prefer `status: stable`; every concept
in this bundle is `status: draft` until a human verifies it.

- [getting-started.md](/getting-started.md) — what this package is, the
  layering, and where to start
- [projection.md](/projection.md) — the one-call rule that keeps this a
  binding
- [runtime.md](/runtime.md) — GObject, Registry, Lifetime, Bridge
- [generation.md](/generation.md) — annotations + GIR → committed output
- [helpers.md](/helpers.md) — C-named int-in / int-out functions
- [enums-and-contracts.md](/enums-and-contracts.md) — reachable GIR
  enums and GTK interfaces as PHP interfaces
- [constructors.md](/constructors.md) — `new()`, `OBTAIN_ONLY`, the
  inherited-`new()` widen
- [signals.md](/signals.md) — one DTO method per GIR signal
- [values.md](/values.md) — GdkRGBA, GdkRectangle, graphene

# Orientation

- [orientation/](/orientation/) — published docs line and how it relates
  to this bundle

# Traps

- [traps/](/traps/) — measured hazards that look like binding bugs and
  are not
