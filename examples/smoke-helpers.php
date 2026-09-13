<?php
/*
 * Spine proof — helper API.
 * Port of ext-gtk examples/smoke.php onto C-named int-in / int-out helpers.
 * Window + button, a real "clicked" signal into a PHP callable,
 * close-request return-value writeback (veto then allow), the handle
 * registry, the property fallback, and the connect guard failure paths.
 * Needs the box's logged-in seat. Prints SMOKE_OK when everything held.
 * GtkApplication / GtkApplicationWindow are generated on the spine but are
 * not constructed here — an application window must be created in activate.
 *
 * Run on the Linux box: php examples/smoke-helpers.php
 */

declare(strict_types=1);

use Jovian\Bindings\Gtk\Enums\GtkOrientation;
use Jovian\Bindings\Gtk\Runtime\Bridge;
use Jovian\Bindings\Gtk\Runtime\Lifetime;

$root = dirname(__DIR__);

/*
 * Standalone checkout (Mac) owns its vendor/; on the Pi this package is
 * path-linked into surface-dev, which owns the vendor tree. Same
 * candidate-list idiom as gtkExtRoot() in tests/Pest.php.
 */
$autoload = null;
foreach ([$root . '/vendor/autoload.php', dirname(__DIR__, 3) . '/vendor/autoload.php'] as $candidate) {
    if (is_file($candidate)) {
        $autoload = $candidate;
        break;
    }
}
if (is_null($autoload)) {
    fwrite(STDERR, "smoke: no autoloader found — run composer install\n");
    exit(1);
}
require $autoload;

foreach ([
    'gtk-window.php',
    'gtk-box.php',
    'gtk-widget.php',
    'gtk-button.php',
    'gtk-label.php',
] as $helper) {
    require_once $root . '/src/Helpers/' . $helper;
}

$failures = 0;

function check(bool $ok, string $marker, string $detail = ''): void
{
    global $failures;
    if ($ok) {
        echo "{$marker}\n";

        return;
    }
    $failures++;
    fwrite(STDERR, "FAIL {$marker}" . ($detail === '' ? '' : " — {$detail}") . "\n");
}

/** Run $fn while collecting PHP warnings; returns [result, warnings[]]. */
function withWarnings(callable $fn): array
{
    $warnings = [];
    set_error_handler(function (int $no, string $msg) use (&$warnings): bool {
        $warnings[] = $msg;

        return true;
    }, E_WARNING);
    try {
        $result = $fn();
    } finally {
        restore_error_handler();
    }

    return [$result, $warnings];
}

if (!extension_loaded('gtk')) {
    fwrite(STDERR, "smoke: the gtk extension is not loaded\n");
    exit(1);
}

try {
    Lifetime::boot();
} catch (\Throwable $e) {
    check(false, 'INIT_OK', $e->getMessage());
    exit(1);
}
check(Lifetime::isBooted(), 'INIT_OK', 'gtk_init_check failed — run from the logged-in seat');
if ($failures > 0) {
    exit(1);
}

// ---- registry ----------------------------------------------------------

$win = gtk_window_new();
check(
    $win !== 0
    && Bridge::isValid($win)
    && Bridge::typeName($win) === 'GtkWindow'
    && Bridge::isA($win, 'GtkWidget')
    && !Bridge::isA($win, 'GtkButton')
    && !Bridge::isValid(0)
    && !Bridge::isValid(12345678),
    'REGISTRY_OK',
    'handle registry answers wrong'
);

// ---- build the scene ---------------------------------------------------

gtk_window_set_title($win, 'gtk smoke');
gtk_window_set_default_size($win, 420, 260);

$box = gtk_box_new(GtkOrientation::VERTICAL->value, 12);
gtk_widget_set_margin_top($box, 20);
gtk_widget_set_margin_bottom($box, 20);
gtk_widget_set_margin_start($box, 20);
gtk_widget_set_margin_end($box, 20);
gtk_window_set_child($win, $box);

$btn = gtk_button_new_with_label('smoke button');
gtk_box_append($box, $btn);
gtk_box_append($box, gtk_label_new('spine'));

check(
    gtk_window_get_title($win) === 'gtk smoke'
    && gtk_button_get_label($btn) === 'smoke button'
    && gtk_window_get_child($win) === $box,
    'ROUNDTRIP_OK',
    'setter/getter round-trip broke'
);

// ---- property fallback (reserved property "x" members) ------------------

Bridge::setProperty($win, 'default-width', 500);
check(
    Bridge::getProperty($win, 'default-width') === 500
    && Bridge::getProperty($win, 'title') === 'gtk smoke',
    'PROPERTY_OK',
    'g_object_get/set_property fallback broke'
);

// ---- clicked: a real GTK signal into a PHP callable ---------------------

$clicks = 0;
$clickedId = Bridge::connect($btn, 'clicked', function (int $sender) use ($btn, &$clicks): void {
    if ($sender === $btn) {
        $clicks++;
    }
});
check($clickedId > 0, 'CONNECT_OK', 'connect(clicked) returned no handler id');

gtk_window_present($win);
$deadline = microtime(true) + 2.0;
while (gtk_widget_get_visible($win) === false && microtime(true) < $deadline) {
    Bridge::pump(50);
}

// gtk_widget_activate on a button returns true immediately, but "clicked"
// rides a ~250ms press animation and needs realization — pump until it lands
// (measured GTK 4.18.6 behaviour, .okf/traps/control-signal-surprises.md).
gtk_widget_activate($btn);
$deadline = microtime(true) + 3.0;
while ($clicks === 0 && microtime(true) < $deadline) {
    Bridge::pump(50);
}
check($clicks === 1, 'CLICKED_OK', "expected 1 click, saw {$clicks}");

Bridge::disconnect($btn, $clickedId);
gtk_widget_activate($btn);
$deadline = microtime(true) + 1.0;
while (microtime(true) < $deadline) {
    Bridge::pump(50);
}
check($clicks === 1, 'DISCONNECT_OK', 'handler fired after disconnect');

// ---- close-request writeback: veto, then allow ---------------------------

$veto = true;
$closeRequests = 0;
Bridge::connect($win, 'close-request', function (int $sender) use (&$veto, &$closeRequests): bool {
    $closeRequests++;

    return $veto;
});

gtk_window_close($win);
Bridge::pump(200);
check(
    $closeRequests === 1 && gtk_widget_get_visible($win) === true,
    'CLOSE_VETO_OK',
    'returning true from close-request did not keep the window open'
);

$veto = false;
gtk_window_close($win);
Bridge::pump(200);
check(
    $closeRequests === 2 && gtk_widget_get_visible($win) === false,
    'CLOSE_OK',
    'returning false from close-request did not close the window'
);

check(Bridge::isValid($win), 'SURVIVES_CLOSE_OK', 'registry ref did not outlive the close');

// ---- connect guard failure paths ----------------------------------------

[$badSignal, $w1] = withWarnings(fn (): int => Bridge::connect($btn, 'no-such-signal', fn () => null));
[$badNotify, $w2] = withWarnings(fn (): int => Bridge::connect($btn, 'notify::use_underline', fn () => null));
[$goodNotify, $w3] = withWarnings(fn (): int => Bridge::connect($btn, 'notify::use-underline', fn () => null));
check(
    $badSignal === 0 && $w1 !== []
    && $badNotify === 0 && $w2 !== [] && str_contains(implode(' ', $w2), 'use-underline')
    && $goodNotify > 0 && $w3 === [],
    'CONNECT_GUARD_OK',
    'bad signal names must fail loudly; underscore notify must point at the dashed name'
);

$notifies = 0;
Bridge::disconnect($btn, $goodNotify);
Bridge::connect($btn, 'notify::use-underline', function () use (&$notifies): void {
    $notifies++;
});
gtk_button_set_use_underline($btn, true);
Bridge::pump(100);
check($notifies === 1, 'NOTIFY_OK', "expected 1 notify, saw {$notifies}");

// ---- verdict -------------------------------------------------------------

Bridge::release($win);

if ($failures > 0) {
    fwrite(STDERR, "smoke: {$failures} failure(s)\n");
    exit(1);
}

echo "SMOKE_OK\n";
