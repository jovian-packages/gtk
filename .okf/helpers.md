---
type: Convention
title: C-named global helpers
description: >-
  One file per bound class. Functions are named from the GIR
  c:identifier, int in / int out, exactly one extension call.
resource: src/Helpers
tags: [helpers, c-identifier, gtk4]
status: draft
generated: { by: cursor-grok-4.6/cursor, at: "2026-08-29T04:10:00Z" }
sources:
  - id: helper-path
    resource: scripts/lib/helper-path.php
    title: Acronym-split kebab
  - id: composer
    resource: composer.json
    title: autoload.files wired from generator output
---

# Why helpers stay

GTK is a C API with real symbol names. `gtk_button_set_label` is
literally what the GTK documentation calls it — the same docs-to-code
identity mapping that earned helpers their place in `microscrap/sdl3`
and `microscrap/glfw`. `jovian/appkit` does **not** ship helpers;
AppKit is not a C API of that shape.

# Shape

```php
function gtk_button_set_label(int $handle, string $label): void
{
    ExtGtkButton::setLabel($handle, $label);
}
```

- Int in, int out. No DTO boxing inside the helper.
- One file per class: `src/Helpers/gtk-button.php`.
- Filenames from `helperRelPath()` — leading-acronym kebab. See
  [helper kebab](/traps/helper-kebab.md).[^helper-path]
- Composer `autoload.files` lists every helper. The generator rewrites
  that list on each `GEN_OK` run.[^composer]

# Loading

```php
require 'vendor/autoload.php';
// gtk_button_new() is now a global function
```
