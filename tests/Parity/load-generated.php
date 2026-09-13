<?php

declare(strict_types=1);

/**
 * Autoload every generated Gtk/Gio DTO in a fresh process.
 * Lives under tests/ so the Pi checkout can run it without .unlazy/.
 * Prints LOADABILITY_OK.
 */

$root = dirname(__DIR__, 2);

/*
 * Standalone checkout (Mac) owns its vendor/; on the Pi this package is
 * path-linked into surface-dev, which owns the vendor tree. Same
 * candidate-list idiom as gtkExtRoot() in tests/Pest.php, so this gate is
 * runnable on the Pi as intended.
 */
$autoload = null;
foreach ([$root . '/vendor/autoload.php', dirname(__DIR__, 4) . '/vendor/autoload.php'] as $candidate) {
    if (is_file($candidate)) {
        $autoload = $candidate;
        break;
    }
}
if (is_null($autoload)) {
    fwrite(STDERR, "load-generated: no autoloader found — run composer install\n");
    exit(1);
}
require $autoload;

$classes = [];
foreach (['Gtk', 'Gio', 'Gdk'] as $ns) {
    foreach (glob($root . '/src/' . $ns . '/*.php') ?: [] as $file) {
        $classes[] = 'Jovian\\Bindings\\Gtk\\' . $ns . '\\' . basename($file, '.php');
    }
}
sort($classes);

if ($classes === []) {
    fwrite(STDERR, "no generated Gtk/Gio/Gdk classes under src/\n");
    exit(1);
}

foreach ($classes as $fqcn) {
    if (!class_exists($fqcn, true)) {
        fwrite(STDERR, "failed to autoload {$fqcn}\n");
        exit(1);
    }
}

echo 'loaded=' . count($classes) . "\n";
echo "LOADABILITY_OK\n";
