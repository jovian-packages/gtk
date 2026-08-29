---
type: Trap
title: Forgetting boot() is a hard crash on helpers
description: >-
  Lifetime::boot() is never implicit. DTO construction throws NotBooted.
  A raw helper call before gtk_init_check is a native crash.
tags: [traps, lifetime, boot]
status: draft
generated: { by: cursor-grok-4.6/cursor, at: "2026-08-29T04:10:00Z" }
sources:
  - id: lifetime
    resource: src/Runtime/Lifetime.php
    title: boot + assertBooted
  - id: notbooted
    resource: src/Runtime/NotBooted.php
    title: Pre-boot exception
---

# What happens

`GObject::__construct` calls `Lifetime::assertBooted()`. Before
`boot()`, that throws `NotBooted` with a message that names
`Lifetime::boot()`.[^lifetime][^notbooted]

Helpers do not go through `GObject`. Calling `gtk_window_new()` before
`Bridge::init()` is the extension's hard crash, not a PHP exception.

# What to do

Call `Lifetime::boot()` once at process start. It is idempotent. Do
not add an implicit init to a DTO constructor — deciding *when* GTK
initialises is a policy, and policy is out of scope.

[^lifetime]: boot + assertBooted
[^notbooted]: Pre-boot exception
