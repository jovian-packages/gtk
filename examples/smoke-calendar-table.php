<?php
/*
 * Wave C proof — DTO API.
 * Boxes a GtkCalendar and a GtkColumnView through the Registry.
 * Calendar year/month/day round-trips (month is 0-based, as GTK reports
 * it). ColumnView is minted over a GtkStringList via GtkSingleSelection.
 * Needs the box's logged-in seat. Prints OK when everything held.
 *
 * Run on the Linux box: php examples/smoke-calendar-table.php
 */

declare(strict_types=1);

use Jovian\Bindings\Gtk\Gtk\GtkCalendar;
use Jovian\Bindings\Gtk\Gtk\GtkColumnView;
use Jovian\Bindings\Gtk\Gtk\GtkSingleSelection;
use Jovian\Bindings\Gtk\Gtk\GtkStringList;
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

$calendar = GtkCalendar::new();
check(
    $calendar->handle !== 0
    && $calendar->isValid()
    && $calendar->typeName() === 'GtkCalendar'
    && Registry::box($calendar->handle) === $calendar,
    'CALENDAR_BOX_OK',
    'calendar did not box through the Registry'
);

$calendar->setYear(2024)->setMonth(8)->setDay(12);
check(
    $calendar->getYear() === 2024
    && $calendar->getMonth() === 8
    && $calendar->getDay() === 12,
    'CALENDAR_DATE_OK',
    'y/m/d=' . $calendar->getYear() . '/' . $calendar->getMonth() . '/' . $calendar->getDay()
);

$list = GtkStringList::new(['0', '1']);
$selection = GtkSingleSelection::new($list);
$view = GtkColumnView::new($selection);
check(
    $view->handle !== 0
    && $view->isValid()
    && $view->typeName() === 'GtkColumnView'
    && Registry::box($view->handle) === $view
    && $view->getModel() === $selection,
    'TABLE_BOX_OK',
    'column view did not box through the Registry'
);

if ($failures > 0) {
    fwrite(STDERR, "smoke: {$failures} failure(s)\n");
    exit(1);
}

echo "OK\n";
