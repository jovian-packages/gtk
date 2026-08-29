---
type: Convention
title: DTO constructors and the inherited new() widen
description: >-
  DTOs expose new() because PHP permits reserved words. Helpers keep
  the C name. One child today widens an inherited new() to an optional
  parameter and throws on null.
tags: [constructors, lsp, gtk4]
status: draft
generated: { by: cursor-grok-4.6/cursor, at: "2026-08-29T04:10:00Z" }
sources:
  - id: app-window
    resource: src/Gtk/GtkApplicationWindow.php
    title: GtkApplicationWindow::new widen
  - id: obtain
    resource: scripts/generate.php
    title: OBTAIN_ONLY guard
---

# Names

The extension names constructors `new_` because `new` is a Zephir
reserved word. PHP allows reserved words as method names, so DTOs
expose `GtkButton::new()` — closer to `gtk_button_new()` than the
extension could be. Helpers keep the exact C name.

Where GIR takes a parent, the PHP static constructor takes the parent
DTO: `GtkApplicationWindow::new(GtkApplication $app)` is a faithful
projection. The AppKit equivalent would be invention.

# OBTAIN_ONLY

These classes have no constructor and the DTO must not invent one:
`GtkSettings`, `GdkDisplay`, `GtkRange`, `GtkNotebookPage`,
`GtkStackPage`.[^obtain] `GtkScale` inherits `GtkRange` and is clear
because the ancestor declares no `new`.

# The inherited `new()` collision

GTK declares `gtk_window_new(void)` and
`gtk_application_window_new(GtkApplication*)` as unrelated C
functions. PHP requires `GtkApplicationWindow::new()` to be compatible
with the `GtkWindow::new()` it inherits, and an added *required*
parameter is not.

The generator widens the child to an optional parameter —
`new(?GtkApplication $application = null)` — and throws immediately
when it is null, because GIR marks the argument non-nullable.[^app-window]
This keeps `new()` on every class and invents no method name.

It affects exactly one class today. `GtkApplication` and `GApplication`
declare identical signatures. `GtkToggleButton` matches `GtkButton`.
The loadability gate is what will catch the next one.

Do not construct `GtkApplicationWindow` in the smoke. An application
window must be created in `activate`.

[^app-window]: GtkApplicationWindow::new widen
[^obtain]: OBTAIN_ONLY guard
