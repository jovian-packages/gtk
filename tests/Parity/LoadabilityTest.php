<?php

declare(strict_types=1);

it('autoloads every generated Gtk and Gio class in a subprocess', function (): void {
    $script = dirname(__DIR__, 2) . '/.unlazy/jovian-gtk/scripts/load-generated.php';
    $output = [];
    $code = 0;
    exec('php ' . escapeshellarg($script) . ' 2>&1', $output, $code);

    expect($code)->toBe(0)
        ->and(implode("\n", $output))->toContain('LOADABILITY_OK');
});
