---
type: Convention
title: Projection, not composition
description: >-
  A method or helper is legitimate only if it is exactly one extension
  call with the same arguments in the same order.
tags: [binding, projection, gtk4]
status: draft
generated: { by: cursor-grok-4.6/cursor, at: "2026-08-29T04:10:00Z" }
sources:
  - id: spec
    resource: docs/superpowers/specs/2026-08-28-jovian-gtk-design.md
    title: Verified decision — projection not composite
  - id: decision
    resource: "neo4j://Decision/jovian wrappers are projections not composites"
    title: Angel decision 2026-08-28
---

# The one rule

`gtk_button_set_label($btn, 'x')` and `$btn->setLabel('x')` are both
`Gtk\Gtk\GtkButton\GtkButton::setLabel($h, 'x')` in a different
shape.[^spec] Anything that bundles several calls is a framework and is
out of scope.

# What that forbids

- Convenience constructors that pick a style mask, backing store, or
  default child.
- Implicit `Bridge::init()`.
- A main-loop policy (`g_application_run` stays reserved on the
  extension).
- Layout helpers, shared AppKit code, or a cross-platform widget type.

Those belong to `jovian/venusian-gtk` and Surface.

# What that allows

- DTO methods that unwrap an enum (`GtkOrientation::VERTICAL->value`)
  and pass the int through. Still one extension call.
- Signal methods that fill in the GIR signal name and call
  `Bridge::connect`. Still one call.
- Constructor `new()` that calls `Ext::new_()` once. The extension's
  only sanctioned composite is constructor + registry sink, and that
  lives below this package.

[^spec]: Verified decision — projection not composite
[^decision]: Angel decision 2026-08-28
