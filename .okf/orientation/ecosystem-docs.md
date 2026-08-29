---
type: Reference
title: Ecosystem docs
description: Published Venusian ecosystem docs for jovian/gtk 0.8.x.
resource: "https://venusian.projectsaturnstudios.com/ecosystem/jovian/gtk/0.8.x/overview"
tags: [orientation, docs, ecosystem, 0.8]
status: draft
generated: { by: cursor-grok-4.6/cursor, at: "2026-08-29T04:10:00Z" }
sources:
  - id: readme
    resource: README.md
    title: README docs pointer
  - id: composer
    resource: composer.json
    title: homepage and support.docs URLs
  - id: pages
    resource: docs/ecosystem/0.8.x
    title: Package-owned 0.8.x page set
---

# Entrypoint

Human-facing package docs live on the Venusian ecosystem site:[^overview][^readme][^composer]

[https://venusian.projectsaturnstudios.com/ecosystem/jovian/gtk/0.8.x/overview](https://venusian.projectsaturnstudios.com/ecosystem/jovian/gtk/0.8.x/overview)

The same eight-page set is owned in-repo at
[`docs/ecosystem/0.8.x/`](/docs/ecosystem/0.8.x) and seeded on the
ScrapyardIO ecosystem host under `/ecosystem/jovian/gtk/0.8.x`.[^pages]
README badges and `composer.json` `homepage` / `support.docs` point at
the Venusian overview.

# How agents should use it

- Prefer this OKF bundle for **in-repo** agent rules (projection,
  runtime, generation, traps).
- Prefer the ecosystem site for **published** narrative docs aimed at
  humans.
- When either drifts from `src/` or the README helper table, update the
  stale side and note it in [log.md](/log.md).

# Related

* [Getting started](/getting-started.md)

[^readme]: README docs pointer
[^composer]: homepage and support.docs URLs
[^overview]: Ecosystem overview page
[^pages]: Package-owned 0.8.x page set
