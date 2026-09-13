---
type: Convention
title: GIR signals as DTO methods
description: >-
  One method per GIR-declared signal. Each is exactly Bridge::connect
  with the signal name filled in. Return-value writeback makes
  close-request work.
tags: [signals, gir, bridge]
status: draft
generated: { by: cursor-grok-4.6/cursor, at: "2026-08-29T04:10:00Z" }
sources:
  - id: spec
    resource: docs/superpowers/specs/2026-08-28-jovian-gtk-design.md
    title: Signals section
  - id: smoke
    resource: examples/smoke-dto.php
    title: clicked + close-request writeback
---

# Projection

GIR lists every signal each class declares. The generator emits one
method per real signal: `$win->onCloseRequest(...)`,
`$btn->onClicked(...)`. Each is
`Bridge::connect($h, 'close-request', $cb)` with the name filled
in — not an invented event.[^spec]

Handles arriving in the callback are boxed through the
[Registry](/runtime.md), so the sender is the object you already have.

Parity excludes `^on[A-Z]` methods before comparing DTO count to the
`@zep` count. The extras on `GtkWidget` / `GtkButton` / `GtkEntry` are
exactly those GIR signals.

# Writeback

`Bridge::connect` marshals GValues both ways. Returning `true` from
`close-request` vetoes the close; `false` lets GTK destroy the
window.[^smoke] That is why the smoke runs veto then allow.

The same path carries `GtkGLArea::onRender`, whose GIR return is
`gboolean`: a handler that drew the frame itself returns `true` to stop
GTK's default handling. Nothing had to be added to the extension's Bridge
for the GL wave — `phpgtk_zval_to_gvalue` already branches on
`G_TYPE_BOOLEAN`. `examples/proof_glarea_typed.php` is the worked
example.

# Connect guard

Unknown signal names fail with `E_WARNING` and return `0`. A
`notify::` detail with underscores is rejected and the warning points
at the dashed property name. Use `notify::use-underline`, not
`notify::use_underline`.

[^spec]: Signals section
[^smoke]: clicked + close-request writeback
