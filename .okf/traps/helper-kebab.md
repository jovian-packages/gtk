---
type: Trap
title: GApplication helper path is g-application.php
description: >-
  helperRelPath must split leading acronyms. GApplication is
  g-application.php, not gapplication.php. GtkButton stays gtk-button.php.
tags: [traps, helpers, kebab]
status: draft
generated: { by: cursor-grok-4.6/cursor, at: "2026-08-29T04:10:00Z" }
sources:
  - id: helper-path
    resource: scripts/lib/helper-path.php
    title: Shared kebab rule
---

# The rule

One shared `scripts/lib/helper-path.php`:[^helper-path]

1. `([A-Z]+)([A-Z][a-z])` — split a leading acronym from the next word
2. `([a-z0-9])([A-Z])` — split lower-to-upper camel humps

| Class | File |
|---|---|
| `GtkButton` | `gtk-button.php` |
| `GApplication` | `g-application.php` |
| `GListStore` | `g-list-store.php` |
| `GMenu` | `g-menu.php` |
| `GtkApplicationWindow` | `gtk-application-window.php` |

A WP3-era Pi tree still had `gaction.php` / `glist-store.php` /
`gmenu.php`. Reflection against that tree would pass and prove nothing
about current helpers. Sync before any Pi gate.

[^helper-path]: Shared kebab rule
