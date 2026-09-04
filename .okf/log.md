# Update Log

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
