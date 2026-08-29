# gtk

GTK4 projected 1:1 into PHP over `ext-gtk` ^0.8.0. One DTO method or C-named
helper per extension call, no opinions: this package is the extension plus
the enum values and PHP names the extension deliberately withholds.
Composition belongs in `jovian/venusian-gtk` (and Surface for a
cross-platform abstraction) — not here. The AppKit sibling
(`jovian/appkit`) is shape-parallel on purpose and shares no code.

Requires Linux, GTK 4.18+, PHP 8.4+, and `ext-gtk` 0.8.0. Call
`Lifetime::boot()` once before any GTK object is constructed. Nothing
initialises the extension for you.

## Projection rules

| GTK / GLib | this package |
|---|---|
| `GtkButton` | `Jovian\Bindings\Gtk\Gtk\GtkButton` — instance methods, one `int $handle` |
| `gtk_button_set_label(button, label)` | `$btn->setLabel('x')` and `gtk_button_set_label($h, 'x')` |
| `gtk_button_new()` | `GtkButton::new()` — PHP allows the reserved word; helpers keep `gtk_button_new()` |
| gboolean / int, guint, enum, flags / double | `bool` / `int` / `Enum\|int` (enumerations) / `int` (bitfields) / `float` |
| `const char*` | `string`, or `?string` when nullable |
| any `GObject*` | boxed DTO, or `int` handle on the helper; 0 = NULL |
| `GdkRGBA` / `GdkRectangle` / graphene structs | `Values\*` with `fromArray()` / `toArgs()` |
| scalar out-params (`int*`, …) | assoc array, keys = C parameter names (`getDefaultSize` → `{width, height}`) |
| signals | one DTO method per GIR signal: `$btn->onClicked(...)` is `Bridge::connect($h, 'clicked', $cb)` |
| inherited methods | bound once on the declaring class; `$button->setHexpand(...)` is `GtkWidget::setHexpand` |

A method is legitimate only if it is exactly one extension call with the
same arguments in the same order. `gtk_button_set_label($btn, 'x')` and
`$btn->setLabel('x')` are both `GtkButton::setLabel($h, 'x')` in a
different shape.

Helpers are int-in / int-out and named from the GIR `c:identifier` —
literally what the GTK docs call the function. DTOs box the same calls.
You can drop to a raw handle at any point.

All glue lives in `Jovian\Bindings\Gtk\Runtime\Bridge`, a 1:1 projection
of `Gtk\Bridge\Bridge`: `init`, the handle registry
(`retain`/`release`/`isValid`/`typeName`/`isA`), `pump`,
`connect`/`disconnect` (with return-value writeback, so `close-request`
handlers work), and `getProperty`/`setProperty`.
`Lifetime::boot()` is the only production `Bridge::init()` caller.

## Example

```php
use Jovian\Bindings\Gtk\Enums\GtkOrientation;
use Jovian\Bindings\Gtk\Gtk\GtkBox;
use Jovian\Bindings\Gtk\Gtk\GtkButton;
use Jovian\Bindings\Gtk\Gtk\GtkWindow;
use Jovian\Bindings\Gtk\Runtime\Bridge;
use Jovian\Bindings\Gtk\Runtime\Lifetime;
use Jovian\Bindings\Gtk\Runtime\Registry;

Lifetime::boot();

$win = GtkWindow::new();
$win->setTitle('hello from PHP');
$win->setDefaultSize(420, 260);

$box = GtkBox::new(GtkOrientation::VERTICAL, 12);
$box->setMarginTop(20);
$win->setChild($box);

$btn = GtkButton::newWithLabel('Click me');
$box->append($btn);

$btn->onClicked(function (int $button) use ($btn): void {
    if (Registry::box($button) === $btn) {
        echo "clicked\n";
    }
});

$open = true;
$win->onCloseRequest(function () use (&$open): bool {
    $open = false;
    return false;                                    // writeback: false lets GTK close the window
});

$win->present();
while ($open) {
    Bridge::pump(50);                                // PHP drives the main loop
}
```

The same scene as C-named helpers is in
[`examples/smoke-helpers.php`](examples/smoke-helpers.php). The DTO
port is [`examples/smoke-dto.php`](examples/smoke-dto.php). Both print
`SMOKE_OK` on a logged-in Linux seat.

## Working on this package

`src/Gtk/**`, `src/Gio/**`, `src/Contracts/**`, `src/Enums/**`,
`src/Helpers/**`, and `src/Runtime/GeneratedTypeMap.php` are generated
and must never be hand-edited. The audit truth is the vendored
GObject-Introspection XML in `php-io-extensions/gtk/scripts/gir/`
(GTK 4.18.6). See [`AGENTS.md`](AGENTS.md) for the rules and
[`.okf/`](.okf/index.md) for the knowledge bundle.

```bash
# Mac (development)
php scripts/generate.php --ext=../../php-io-extensions/gtk   # GEN_OK; rewrites autoload.files
vendor/bin/pest                                              # skips ext-gtk tests when unloaded

# Linux box (fnk0107 — ext-gtk 0.8.0 loaded)
fnk 'cd ~/Development/PHP/jovian/gtk && vendor/bin/pest'
fnk 'cd ~/Development/PHP/jovian/gtk && php examples/smoke-dto.php'
```
