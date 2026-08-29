---
type: Convention
title: Real structs vs ad-hoc out-params
description: >-
  GdkRGBA, GdkRectangle, and graphene become readonly Values with
  fromArray/toArgs. Ad-hoc scalar out-param groups stay assoc arrays.
resource: src/Values
tags: [values, structs, out-params]
status: draft
generated: { by: cursor-grok-4.6/cursor, at: "2026-08-29T04:10:00Z" }
sources:
  - id: rgba
    resource: src/Values/GdkRGBA.php
    title: GdkRGBA value object
  - id: spec
    resource: docs/superpowers/specs/2026-08-28-jovian-gtk-design.md
    title: Values and multiple returns
---

# Real structs

`GdkRGBA`, `GdkRectangle`, `graphene_rect_t`, and `graphene_point_t`
are readonly objects under `src/Values/` with `fromArray()` and
`toArgs()`.[^rgba] Generated methods take and return them because the
C signature really is one struct argument. The component doubles are
the extension's ABI concession.

# Ad-hoc groups

`gtk_window_get_default_size(win, &width, &height)` arrives from the
extension as `{width, height}`. Those stay assoc arrays with
array-shape docblocks. Minting a class per method would be
invention.[^spec]

There are 41 array returns on the bound surface, minus the real
structs.

[^rgba]: GdkRGBA value object
[^spec]: Values and multiple returns
