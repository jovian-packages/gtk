---
type: Reference
title: Getting started — jovian/gtk
description: >-
  jovian/gtk 0.8.0 is the typed projection of ext-gtk: C-named helpers,
  int-backed enums, and handle DTOs. No composition.
tags: [orientation, gtk4, jovian]
status: draft
generated: { by: cursor-grok-4.6/cursor, at: "2026-08-29T04:10:00Z" }
sources:
  - id: spec
    resource: docs/superpowers/specs/2026-08-28-jovian-gtk-design.md
    title: jovian/gtk design spec
  - id: readme
    resource: README.md
    title: Package README
  - id: composer
    resource: composer.json
    title: Package metadata 0.8.0
---

# Overview

`jovian/gtk` (`Jovian\Bindings\Gtk\`) sits between `ext-gtk` and
`jovian/venusian-gtk`. The extension binds GTK 1:1 and withholds enum
values and all composition. This package adds the enums, the C-named
helpers, and a typed DTO projection. It adds no composition.[^spec]

Layering:

```text
libgtk-4
  └── ext-gtk                         (php-io-extensions/gtk @ 0.8.0)
        └── jovian/gtk                ← this package
              └── jovian/venusian-gtk (composition)
                    └── venusian/surface (cross-platform abstraction)
```

# Start here

1. Call [`Lifetime::boot()`](/runtime.md) once. Nothing else initialises
   GTK.
2. Construct with [`GtkButton::new()`](/constructors.md) or
   `gtk_button_new()`.
3. Pick one surface: DTO methods or [C-named helpers](/helpers.md). Both
   are the same extension call.
4. Connect signals with [`$btn->onClicked(...)`](/signals.md).

See [`examples/smoke-dto.php`](../examples/smoke-dto.php) and
[`examples/smoke-helpers.php`](../examples/smoke-helpers.php).

# What it is not

- Not a widget toolkit with layout helpers or convenience constructors.
- Not a shared library with `jovian/appkit`. The two packages are
  shape-parallel and share no code.
- Not allowed to invent a method that bundles several extension calls.
  That is the [projection](/projection.md) rule.

[^spec]: jovian/gtk design spec
[^readme]: Package README
[^composer]: Package metadata 0.8.0
