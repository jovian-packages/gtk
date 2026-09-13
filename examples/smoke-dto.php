<?php
/*
 * Spine proof — DTO API.
 * Port of ext-gtk examples/smoke.php onto typed handle DTOs.
 * Window + button, a real "clicked" signal into a PHP callable,
 * close-request return-value writeback (veto then allow), the handle
 * registry, the property fallback, and the connect guard failure paths.
 * Needs the box's logged-in seat. Prints SMOKE_OK when everything held.
 * GtkApplication / GtkApplicationWindow are generated on the spine but are
 * not constructed here — an application window must be created in activate.
 *
 * Run on the Linux box: php examples/smoke-dto.php
 */

declare(strict_types=1);

use Jovian\Bindings\Gtk\Enums\GtkOrientation;
use Jovian\Bindings\Gtk\Gtk\GtkBox;
use Jovian\Bindings\Gtk\Gtk\GtkButton;
use Jovian\Bindings\Gtk\Gtk\GtkLabel;
use Jovian\Bindings\Gtk\Gtk\GtkWindow;
use Jovian\Bindings\Gtk\Runtime\Bridge;
use Jovian\Bindings\Gtk\Runtime\Lifetime;
use Jovian\Bindings\Gtk\Runtime\Registry;

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

$win = GtkWindow::new();
check(
    $win->handle !== 0
    && $win->isValid()
    && $win->typeName() === 'GtkWindow'
    && $win->isA('GtkWidget')
    && !$win->isA('GtkButton')
    && !Bridge::isValid(0)
    && !Bridge::isValid(12345678),
    'REGISTRY_OK',
    'handle registry answers wrong'
);

// ---- build the scene ---------------------------------------------------

$win->setTitle('gtk smoke');
$win->setDefaultSize(420, 260);

$box = GtkBox::new(GtkOrientation::VERTICAL, 12);
$box->setMarginTop(20)
    ->setMarginBottom(20)
    ->setMarginStart(20)
    ->setMarginEnd(20);
$win->setChild($box);

$btn = GtkButton::newWithLabel('smoke button');
$box->append($btn);
$box->append(GtkLabel::new('spine'));

check(
    $win->getTitle() === 'gtk smoke'
    && $btn->getLabel() === 'smoke button'
    && $win->getChild() === $box,
    'ROUNDTRIP_OK',
    'setter/getter round-trip broke'
);

// ---- property fallback (reserved property "x" members) ------------------

$win->setProperty('default-width', 500);
check(
    $win->getProperty('default-width') === 500
    && $win->getProperty('title') === 'gtk smoke',
    'PROPERTY_OK',
    'g_object_get/set_property fallback broke'
);

// ---- clicked: a real GTK signal into a PHP callable ---------------------

$clicks = 0;
$clickedId = $btn->onClicked(function (int $sender) use ($btn, &$clicks): void {
    if (Registry::box($sender) === $btn) {
        $clicks++;
    }
});
check($clickedId > 0, 'CONNECT_OK', 'connect(clicked) returned no handler id');

$win->present();
$deadline = microtime(true) + 2.0;
while ($win->getVisible() === false && microtime(true) < $deadline) {
    Bridge::pump(50);
}

// gtk_widget_activate on a button returns true immediately, but "clicked"
// rides a ~250ms press animation and needs realization — pump until it lands
// (measured GTK 4.18.6 behaviour, .okf/traps/control-signal-surprises.md).
$btn->activate();
$deadline = microtime(true) + 3.0;
while ($clicks === 0 && microtime(true) < $deadline) {
    Bridge::pump(50);
}
check($clicks === 1, 'CLICKED_OK', "expected 1 click, saw {$clicks}");

Bridge::disconnect($btn->handle, $clickedId);
$btn->activate();
$deadline = microtime(true) + 1.0;
while (microtime(true) < $deadline) {
    Bridge::pump(50);
}
check($clicks === 1, 'DISCONNECT_OK', 'handler fired after disconnect');

// ---- close-request writeback: veto, then allow ---------------------------

$veto = true;
$closeRequests = 0;
$win->onCloseRequest(function (int $sender) use ($win, &$veto, &$closeRequests): bool {
    if (Registry::box($sender) === $win) {
        $closeRequests++;
    }

    return $veto;
});

$win->close();
Bridge::pump(200);
check(
    $closeRequests === 1 && $win->getVisible() === true,
    'CLOSE_VETO_OK',
    'returning true from close-request did not keep the window open'
);

$veto = false;
$win->close();
Bridge::pump(200);
check(
    $closeRequests === 2 && $win->getVisible() === false,
    'CLOSE_OK',
    'returning false from close-request did not close the window'
);

check($win->isValid(), 'SURVIVES_CLOSE_OK', 'registry ref did not outlive the close');

// ---- connect guard failure paths ----------------------------------------

[$badSignal, $w1] = withWarnings(fn (): int => Bridge::connect($btn->handle, 'no-such-signal', fn () => null));
[$badNotify, $w2] = withWarnings(fn (): int => Bridge::connect($btn->handle, 'notify::use_underline', fn () => null));
[$goodNotify, $w3] = withWarnings(fn (): int => Bridge::connect($btn->handle, 'notify::use-underline', fn () => null));
check(
    $badSignal === 0 && $w1 !== []
    && $badNotify === 0 && $w2 !== [] && str_contains(implode(' ', $w2), 'use-underline')
    && $goodNotify > 0 && $w3 === [],
    'CONNECT_GUARD_OK',
    'bad signal names must fail loudly; underscore notify must point at the dashed name'
);

$notifies = 0;
Bridge::disconnect($btn->handle, $goodNotify);
Bridge::connect($btn->handle, 'notify::use-underline', function () use (&$notifies): void {
    $notifies++;
});
$btn->setUseUnderline(true);
Bridge::pump(100);
check($notifies === 1, 'NOTIFY_OK', "expected 1 notify, saw {$notifies}");

// ---- verdict -------------------------------------------------------------

if ($failures > 0) {
    fwrite(STDERR, "smoke: {$failures} failure(s)\n");
    exit(1);
}

echo "SMOKE_OK\n";
