---
type: Runbook
title: Generator — annotations joined to vendored GIR
description: >-
  scripts/generate.php ports the extension parsers, hard-fails unmatched
  annotations, and emits DTOs, helpers, enums, contracts, the type map,
  and composer autoload.files.
resource: scripts/generate.php
tags: [generator, gir, annotations]
status: draft
generated: { by: cursor-grok-4.6/cursor, at: "2026-09-12T20:10:00Z" }
sources:
  - id: generate
    resource: scripts/generate.php
    title: Generator entry
  - id: emit
    resource: scripts/lib/emit.php
    title: DTO / helper / type-map / autoload emitter
  - id: gir
    resource: ../../php-io-extensions/gtk/scripts/gir
    title: Vendored GIR GTK 4.18.6
---

# Inputs

`--ext=` defaults to `../../php-io-extensions/gtk`.

1. **`src/*.h` `@zep` annotations** — the authoritative list of what
   exists. Parsers are ported from the extension's `audit-gir.php` and
   `gen-zep.php`.[^generate]
2. **Vendored GIR** (gzipped XML, GTK 4.18.6 / GLib 2.84.4 / Pango
   1.56.3) — hierarchy, interfaces, real types, enum members, signals,
   and the `c:identifier` the helpers are named from.[^gir]

# Rules the generator replays

- `escapeReserved()` — an annotation reading `new()` is a PHP-visible
  `new_()` on the extension. Never call the annotation name verbatim.
- Constructors return `int` and have no `handle` parameter.
- `OBTAIN_ONLY`: `GtkSettings`, `GdkDisplay`, `GtkRange`,
  `GtkNotebookPage`, `GtkStackPage` — no invented constructor. GIR
  `abstract="1"` classes get none either: `GdkGLContext` is obtained
  from `GtkGLArea::getContext` or its own static `getCurrent`.
- Unmatched annotations and interface-trait collisions are hard
  failures. Bridge (12 methods) is glue and is skipped, not unmatched.

# Output (committed, never hand-edited)

`src/Gtk/`, `src/Gio/`, `src/Gdk/`, `src/Contracts/`, `src/Enums/`,
`src/Helpers/`,
`src/Runtime/GeneratedTypeMap.php`, and `composer.json`
`autoload.files`.[^emit]

Success token: `GEN_OK`.

# Mac / Pi

Generation is pure PHP and runs on the Mac. The Pi checkout must be
synced (`src tests examples composer.json phpunit.xml`, never
`vendor/` or `.unlazy/`) before identity, lifetime, reflection, or
smoke is trusted. GIR-backed Pest tests call `gtkExtRoot()` in
`tests/Pest.php`: Mac sibling `../../php-io-extensions/gtk`, else
the Pi ext checkout at `/home/angel/gtk`. Override with
`JOVIAN_GTK_EXT`. Wave C identity smoke:
`examples/smoke-calendar-table.php`.

[^generate]: Generator entry
[^emit]: DTO / helper / type-map / autoload emitter
[^gir]: Vendored GIR GTK 4.18.6
