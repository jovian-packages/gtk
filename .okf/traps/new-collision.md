---
type: Trap
title: Inherited new() cannot grow a required parameter
description: >-
  GtkApplicationWindow::new must stay compatible with GtkWindow::new.
  The generator widens to an optional GtkApplication and throws on null.
tags: [traps, constructors, lsp]
status: draft
generated: { by: cursor-grok-4.6/cursor, at: "2026-08-29T04:10:00Z" }
sources:
  - id: ctor
    resource: /constructors.md
    title: Constructors concept
---

# Symptom

Loading `GtkApplicationWindow` fatals:

```text
Declaration of GtkApplicationWindow::new(GtkApplication $application)
must be compatible with GtkWindow::new()
```

# Cause

Unrelated C functions share a PHP method name through inheritance.
See [constructors](/constructors.md).[^ctor]

# Fix already in the generator

Emit `new(?GtkApplication $application = null)` and throw when
`is_null($application)`. Do not invent `newWithApplication`. Do not
drop `new()` from the child.

[^ctor]: Constructors concept
