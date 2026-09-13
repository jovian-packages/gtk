<?php

declare(strict_types=1);

/**
 * Pi reflection oracle. Every Ext*::method() call site in the generated
 * surface and Runtime\Bridge must exist on the installed ext-gtk .so.
 * A Mac skip is not REFLECT_OK.
 */

$root = dirname(__DIR__, 2);

if (!extension_loaded('gtk')) {
    fwrite(STDERR, "ext-gtk is not loaded; reflection is a Pi gate\n");
    exit(1);
}

$scanDirs = [
    $root . '/src/Gtk',
    $root . '/src/Gio',
    $root . '/src/Gdk',
    $root . '/src/Helpers',
    $root . '/src/Contracts',
    $root . '/src/Runtime',
];

$useAlias = '/^use\s+([A-Za-z0-9_\\\\]+)\s+as\s+(Ext[A-Za-z0-9_]+)\s*;/m';
$callSite = '/\b(Ext[A-Za-z0-9_]+)::([A-Za-z_][A-Za-z0-9_]*)\s*\(/';

$sites = [];
$helperSites = 0;
$dtoSites = 0;

foreach ($scanDirs as $dir) {
    if (!is_dir($dir)) {
        fwrite(STDERR, "missing scan directory {$dir}\n");
        exit(1);
    }

    $files = glob($dir . '/*.php') ?: [];
    foreach ($files as $file) {
        $source = (string) file_get_contents($file);
        $aliases = [];
        if (preg_match_all($useAlias, $source, $aliasMatches, PREG_SET_ORDER) > 0) {
            foreach ($aliasMatches as $match) {
                $aliases[$match[2]] = $match[1];
            }
        }

        if (preg_match_all($callSite, $source, $calls, PREG_SET_ORDER) === false) {
            fwrite(STDERR, "call-site scan failed in {$file}\n");
            exit(1);
        }

        foreach ($calls as $call) {
            $alias = $call[1];
            $method = $call[2];
            if ($method === 'class') {
                continue;
            }
            if (!isset($aliases[$alias])) {
                fwrite(STDERR, "unresolved alias {$alias}::{$method} in {$file}\n");
                exit(1);
            }

            $sites[] = [
                'file' => $file,
                'fqcn' => $aliases[$alias],
                'method' => $method,
            ];

            if (str_contains($file, '/src/Helpers/')) {
                $helperSites++;
            }
            if (str_contains($file, '/src/Gtk/')
                || str_contains($file, '/src/Gio/')
                || str_contains($file, '/src/Gdk/')) {
                $dtoSites++;
            }
        }
    }
}

if ($sites === [] || $helperSites === 0 || $dtoSites === 0) {
    fwrite(STDERR, "scanner found no Ext* call sites (helpers={$helperSites} dto={$dtoSites})\n");
    exit(1);
}

$bridgeMethods = [
    'init', 'retain', 'release', 'isValid', 'typeName', 'isA',
    'typeFromName', 'pump', 'connect', 'disconnect', 'getProperty', 'setProperty',
];
foreach ($bridgeMethods as $method) {
    $sites[] = [
        'file' => $root . '/src/Runtime/Bridge.php',
        'fqcn' => 'Gtk\\Bridge\\Bridge',
        'method' => $method,
    ];
}

if (method_exists(\Gtk\Gtk\GtkButton\GtkButton::class, 'thisMethodDoesNotExistOnGtk')) {
    fwrite(STDERR, "negative control failed: missing method existed\n");
    exit(1);
}
if (!method_exists(\Gtk\Gtk\GtkButton\GtkButton::class, 'new_')) {
    fwrite(STDERR, "positive control failed: GtkButton::new_ missing on the .so\n");
    exit(1);
}

$missing = [];
$unique = [];
foreach ($sites as $site) {
    $key = $site['fqcn'] . '::' . $site['method'];
    $unique[$key] = true;
    if (!class_exists($site['fqcn'])) {
        $missing[$key] = $site['fqcn'] . ' (class missing)';
        continue;
    }
    if (!method_exists($site['fqcn'], $site['method'])) {
        $missing[$key] = $key;
    }
}

if ($missing !== []) {
    fwrite(STDERR, "missing extension methods:\n" . implode("\n", $missing) . "\n");
    exit(1);
}

echo 'sites=' . count($sites) . ' unique=' . count($unique) . ' helpers=' . $helperSites . ' dto=' . $dtoSites . "\n";
echo "REFLECT_OK\n";
