# Update Log

## 2026-09-13 (GL wave — GtkGLArea, and the first Gdk class)

* **Generation**: ext-gtk 0.8.1's GL wave projected — `GtkGLArea` (16
  methods, all three GIR signals as `onRender` / `onResize` /
  `onCreateContext`) and `GdkGLContext` (19 methods). Reachable enum
  `GdkGLAPI` came with them. Surface is now 90 DTOs / 1457 helpers /
  44 enums / 8 contracts. `GEN_OK` on the Mac.
* **`src/Gdk/` is a new output tree.** `GdkGLContext` is the first `Gdk\`
  class. `scripts/lib/emit.php` already knew the tree (`dtoRelPath` and
  `pruneGenerated` both had a `Gdk` branch), and the PSR-4 root maps
  `Jovian\Bindings\Gtk\` → `src/`, so autoloading needed nothing. What
  did *not* know about it were three Pi-side parity scanners, all of which
  hardcoded Gtk/Gio and would have silently skipped every Gdk class:
  `tests/Parity/reflect-extension.php` (scan dirs **and** the DTO-site
  classifier), `tests/Parity/load-generated.php`, and
  `tests/Parity/HelperNameAgreementTest.php`. All three now walk
  `['Gtk', 'Gio', 'Gdk']`.
* **Constructors**: `GdkGLContext` is GIR `abstract="1"`, so the generator
  emitted no `new()` — you obtain one from `GtkGLArea::getContext` or the
  static `getCurrent`. It extends `Runtime\GObject` directly.
* **Signals**: `onRender` returns `bool` through the extension's existing
  return writeback — no Bridge change was needed. See
  [signals.md](/signals.md).
* **Proof**: `examples/proof_glarea_typed.php` — the typed port of
  ext-gtk's `examples/proof_glarea.php`. On the Pi seat: 118 frames,
  centre RGBA `255,128,64,255`, corner `0,0,0,255`,
  `PROOF_GLAREA_TYPED_OK`. It also pins `TypeMap::resolveWithProbe`: GDK
  hands out a **`GdkWaylandGLContext`**, a runtime subclass absent from
  the generated tables, and the Registry still boxes it to
  `Jovian\Bindings\Gtk\Gdk\GdkGLContext` — the same probe the media
  backends needed in the video wave.
  The GL half of that proof calls **ext-opengl directly**, because
  jovian/ogx is not wired into surface-dev; the GTK half is fully typed.
* **Trap inherited from the extension**: a GtkGLArea on the Pi only gets a
  **GLES 3.1** context. Narrowing allowed-apis to `GdkGLAPI::GL` leaves
  `getContext()` null and render never fires, so the proof allows both and
  picks the shader dialect (`#version 300 es` vs `#version 140`) from
  `GdkGLContext::getApi()`. Full measurement in ext-gtk
  `.okf/traps/glarea-is-gles-on-the-pi.md`. Consequence for this layer:
  nothing above may assume a GtkGLArea means desktop GL.
* **Examples are now runnable from the path-linked Pi checkout.** The Pi
  copy at `/home/angel/Development/PHP/surface-dev/jovian/gtk` has no
  `vendor/` of its own — surface-dev owns it — so every example and
  `tests/Parity/load-generated.php` hardcoding
  `dirname(__DIR__) . '/vendor/autoload.php'` exited with "run composer
  install first" there. They now try the package's own vendor and then
  surface-dev's, the same candidate-list idiom `gtkExtRoot()` uses.
* **Versions**: jovian/gtk 0.8.0 → **0.8.1**, requiring `ext-gtk`
  `^0.8.1`. Consuming path-repo projects need `composer update
  jovian/gtk` for the two new helper files — done on the Pi
  (surface-dev now resolves jovian/gtk 0.8.1).
* Mac gates: 59 passed / 7 skipped (extension-gated), 1902 assertions —
  including a new `tests/Spine/GlSurfaceTest.php`. Pi:
  `LOADABILITY_OK` (90 classes), `REFLECT_OK` (1469 unique call sites),
  `SMOKE_OK`, calendar/table smoke OK, `PROOF_GLAREA_TYPED_OK`.

## 2026-09-12 (Wave C calendar + column view)
* **Generation**: ext-gtk Wave C projected — `GtkCalendar`, `GtkColumnView`,
  `GtkColumnViewColumn`, `GtkListItem`, `GtkSignalListItemFactory`,
  `GtkSelectionModel` (interface + contract), `GtkSingleSelection`,
  `GtkNoSelection`, plus reachable enum `GtkSortType` (`GtkListTabBehavior`
  was already projected; ColumnView now uses it). Surface is
  now 88 DTOs / 1422 helpers / 43 enums / 8 contracts. `GEN_OK` on the Mac.
  `examples/smoke-calendar-table.php` boxes a calendar and a ColumnView
  through the Registry (month is 0-based, as GTK reports it). Parity
  loadability / helper-name tests now use `tests/Parity/load-generated.php`
  instead of `.unlazy/`, so the Pi sync (never `.unlazy/`) can run them.
  `gtkExtRoot()` in `tests/Pest.php` finds the Mac sibling checkout or
  `/home/angel/gtk` on the Pi.

## 2026-09-04 (text-buffer read-back)
* **Generation**: ext unreserved `gtk_text_buffer_get_text` (iters cross as
  int character offsets, -1 = end), so `GtkTextBuffer::getText(startOffset,
  endOffset, includeHiddenChars): ?string` and the
  `gtk_text_buffer_get_text` helper are now projected. Proven on the Pi:
  reflection green, full-buffer and offset-sliced reads both answer.

## 2026-08-31 (video)
* **Generation**: `GtkVideo`/`GtkMediaFile`/`GtkMediaStream` DTOs + helpers projected
  with honest parents (`GtkMediaFile extends GtkMediaStream extends GObject`), plus
  `GtkGraphicsOffloadEnabled`. Mac gates 52 passed.
* **Runtime**: `TypeMap::resolveWithProbe` — a GType name absent from the generated
  tables (media backends and theme engines hand out runtime subclasses, e.g.
  `GtkGstMediaFile` for a `GtkMediaFile`) is resolved by `Bridge::isA` against every
  registered class, deepest lineage wins, verdict cached. `Registry::box` uses it;
  without the probe a boxed backend object fell to plain `GObject` and lost its DTO
  surface. Proven on the Pi.

## 2026-08-30 (css)
* **Generation**: `GtkCssProvider` + `GtkStyleContext` projected; two new helpers in
  composer files — consuming path-repo projects need `composer update jovian/gtk`
  (done on the Pi).

## 2026-08-30
* **Generation**: `Gio\GSimpleActionGroup` projected (implements the `GActionMap`
  contract via the trait); new helper `g-simple-action-group.php` in composer files —
  consuming path-repo projects need `composer update jovian/gtk`.
* **Generator fix**: an interface-typed parameter now hints the *Contract*
  (`Contracts\GAction`, `Contracts\GListModel`) instead of the interface's own DTO
  class. Implementors implement the contract and do not extend the DTO, so the old
  hint raised TypeError on the first real `GActionMap::addAction(GSimpleAction)` call;
  `GtkDropDown::setModel(GListStore)` had the same latent break.

## 2026-08-29

* **Creation**: Seeded the jovian/gtk 0.8.0 bundle for WP5 — projection,
  runtime, generation, helpers, enums, constructors, signals, values,
  ecosystem orientation, and four traps —
  [getting-started](getting-started.md).
