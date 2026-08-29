# Agent guidelines — jovian/gtk

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/)
(excluded from the Composer dist via `.gitattributes` `export-ignore`).
Before changing code or advising on this package: read
[`.okf/index.md`](.okf/index.md) first, open only the concepts the task
needs, prefer `status: stable` over `draft`. When you learn something
durable, update the affected concept(s) and append `.okf/log.md`; new or
changed concepts stay `status: draft` until a human verifies them.

Do **not** create `.okf` folders under `src/Gtk`, `src/Gio`, or other
component trees — knowledge for this package lives at the package root
only.

## Package rules (quick) — 0.8.x

- Composer: `jovian/gtk` **0.8.0**. PHP `^8.4|^8.5|^8.6`. Requires
  `ext-gtk` `^0.8.0`.
- Namespace root is `Jovian\Bindings\Gtk\`.
- **One ext call = one method.** A DTO method or helper is legitimate
  only if it is exactly one extension call with the same arguments in
  the same order. Composition belongs in `jovian/venusian-gtk`.
- **Helpers stay.** C-named, int-in / int-out, named from the GIR
  `c:identifier` (`gtk_button_set_label`). Filenames come from
  `scripts/lib/helper-path.php` (acronym-split kebab:
  `GApplication` → `g-application.php`).
- **Enums live here.** Int-backed, cases FULLY UPPERCASE, no class
  constants. Enumeration parameters are `EnumName|int`. Bitfield
  parameters stay `int` (PHP enums cannot be OR'd).
- Prefer `is_null($var)` over `$var === null`.
- **`Lifetime::boot()` is explicit.** Nothing calls `Bridge::init()`
  except `Lifetime::boot()`. Constructing a DTO before boot throws
  `NotBooted`. Forgetting boot on a raw helper is a native crash.
- **Generated trees are output.** Never hand-edit `src/Gtk/**`,
  `src/Gio/**`, `src/Contracts/**`, `src/Enums/**`, `src/Helpers/**`, or
  `src/Runtime/GeneratedTypeMap.php`. Handwritten trees are
  `src/Runtime/` (except `GeneratedTypeMap`) and `src/Values/`.
- **Generator.** `php scripts/generate.php --ext=../../php-io-extensions/gtk`.
  Ports `collectAnnotations`, `loadGir`, `escapeReserved`, `OBTAIN_ONLY`
  from the extension. An unmatched annotation or an interface-trait
  collision is a hard failure, never a silent skip. Replay
  `escapeReserved()` — an annotation reading `new()` is a PHP-visible
  `new_()` on the extension. After emit, the generator rewrites
  `composer.json` `autoload.files` to the kept helper list.
- **Constructors.** DTOs expose `new()`. `OBTAIN_ONLY` classes
  (`GtkSettings`, `GdkDisplay`, `GtkRange`, `GtkNotebookPage`,
  `GtkStackPage`) have no constructor. Where an ancestor declares an
  incompatible `new()`, the child widens to an optional parameter and
  throws on null — today that is only
  `GtkApplicationWindow::new(?GtkApplication $application = null)`.
- **Identity.** `Runtime\GObject` holds one `int $handle` and no other
  state. `Runtime\Registry` maps handle → `WeakReference<GObject>`.
  Construction retains; destruction releases only if the registry entry
  still points at the dying object. Destructors skip release after
  shutdown.
- **Mac / Pi split.** Generation, parity, enum, and style gates run on
  the Mac. Identity, lifetime, reflection, and smoke run on fnk0107 via
  `fnk '<command>'` (zsh alias; never inline its credentials). Sync
  `src tests examples composer.json phpunit.xml` first — never
  `vendor/` or `.unlazy/`. Extension-gated Pest tests skip when
  `extension_loaded('gtk')` is false.
