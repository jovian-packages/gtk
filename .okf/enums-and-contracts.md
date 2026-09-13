---
type: Convention
title: Reachable enums and GTK interfaces
description: >-
  Int-backed PHP enums mined from GIR types that appear on a bound
  signature. GTK interfaces become PHP interfaces plus generated traits.
resource: src/Enums
tags: [enums, contracts, gir]
status: draft
generated: { by: cursor-grok-4.6/cursor, at: "2026-09-12T19:40:00Z" }
sources:
  - id: enums
    resource: src/Enums
    title: Generated enum tree
  - id: contracts
    resource: src/Contracts
    title: Generated interfaces and *Methods traits
---

# Enumerations and bitfields

Only types reachable from a bound `@zep` signature are generated.
GIR aliases that would duplicate a backed value are skipped
(`GtkAlign::baseline`, `GApplicationFlags::default_flags`). A case
that would start with a digit is prefixed `N_` (`GtkLicense::N_0BSD`).

| Kind | Parameter type | Why |
|---|---|---|
| Enumeration | `GtkOrientation\|int` | unwrap `->value` for the one ext call |
| Bitfield | `int` | PHP enums cannot be OR'd |

Cases are FULLY UPPERCASE. No class constants. Composer classmap is
not required for enums — they live under PSR-4
`Jovian\Bindings\Gtk\`.[^enums]

The enum Pest re-reads the vendored GIR at test time. A GTK bump
without re-vendoring fails that test rather than silently drifting.

# Contracts

Bound GIR interfaces become a PHP interface plus a `*Methods` trait
under `src/Contracts/`. DTOs `implement` the interface and `use` the
trait.[^contracts]

Current set: `GtkEditable`, `GtkOrientable`, `GtkScrollable`,
`GtkActionable`, `GtkSelectionModel`, `GAction`, `GActionMap`,
`GListModel`.

`composer.json` lists `src/Contracts/` as a classmap so
`GtkOrientableMethods` (defined in `GtkOrientable.php`) autoloads.
Without that classmap a DTO `use` of the trait fatals.

`detectInterfaceTraitCollisions()` hard-fails the generator rather
than emitting uncompilable code. GIR signal methods are not part of
the collision scan.

[^enums]: Generated enum tree
[^contracts]: Generated interfaces and *Methods traits
