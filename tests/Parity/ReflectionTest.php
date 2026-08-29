<?php

declare(strict_types=1);

it('every emitted extension call exists on the installed .so', function (): void {
    $script = __DIR__ . '/reflect-extension.php';
    $output = [];
    $code = 0;
    exec('php ' . escapeshellarg($script) . ' 2>&1', $output, $code);

    expect($code)->toBe(0)
        ->and(implode("\n", $output))->toContain('REFLECT_OK');
})->skip(!gtkExtensionLoaded(), 'ext-gtk is not loaded');
