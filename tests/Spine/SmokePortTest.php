<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$markers = [
    'INIT_OK',
    'REGISTRY_OK',
    'ROUNDTRIP_OK',
    'PROPERTY_OK',
    'CONNECT_OK',
    'CLICKED_OK',
    'DISCONNECT_OK',
    'CLOSE_VETO_OK',
    'CLOSE_OK',
    'SURVIVES_CLOSE_OK',
    'CONNECT_GUARD_OK',
    'NOTIFY_OK',
    'SMOKE_OK',
];

it('ports ext-gtk smoke to C-named helpers and to DTOs', function () use ($root, $markers): void {
    $helper = $root . '/examples/smoke-helpers.php';
    $dto = $root . '/examples/smoke-dto.php';
    expect(is_file($helper))->toBeTrue()
        ->and(is_file($dto))->toBeTrue();

    $helperText = (string) file_get_contents($helper);
    $dtoText = (string) file_get_contents($dto);
    expect(trim($helperText))->not->toBe('')
        ->and(trim($dtoText))->not->toBe('');

    foreach ($markers as $marker) {
        expect($helperText)->toContain($marker);
        expect($dtoText)->toContain($marker);
    }

    expect($helperText)->toContain('Lifetime::boot()')
        ->and($helperText)->toContain('gtk_button_new_with_label')
        ->and($helperText)->toContain('gtk_window_new')
        ->and($helperText)->toContain("Bridge::connect(\$btn, 'clicked'")
        ->and($helperText)->toContain("Bridge::connect(\$win, 'close-request'")
        ->and($helperText)->toContain('Bridge::pump(');

    expect($dtoText)->toContain('Lifetime::boot()')
        ->and($dtoText)->toContain('GtkWindow::new()')
        ->and($dtoText)->toContain('GtkButton::newWithLabel')
        ->and($dtoText)->toContain('$btn->onClicked(')
        ->and($dtoText)->toContain('$win->onCloseRequest(')
        ->and($dtoText)->toContain('Bridge::pump(')
        ->and($dtoText)->toContain('Registry::box(');
});

it('runs the helper smoke against a live GTK seat', function () use ($root): void {
    $output = [];
    $code = 0;
    exec('php ' . escapeshellarg($root . '/examples/smoke-helpers.php') . ' 2>&1', $output, $code);
    $text = implode("\n", $output);
    expect($code)->toBe(0)
        ->and($text)->toContain('CLICKED_OK')
        ->and($text)->toContain('CLOSE_VETO_OK')
        ->and($text)->toContain('CLOSE_OK')
        ->and($text)->toContain('SMOKE_OK');
})->skip(!gtkExtensionLoaded(), 'ext-gtk is not loaded');

it('runs the DTO smoke against a live GTK seat', function () use ($root): void {
    $output = [];
    $code = 0;
    exec('php ' . escapeshellarg($root . '/examples/smoke-dto.php') . ' 2>&1', $output, $code);
    $text = implode("\n", $output);
    expect($code)->toBe(0)
        ->and($text)->toContain('CLICKED_OK')
        ->and($text)->toContain('CLOSE_VETO_OK')
        ->and($text)->toContain('CLOSE_OK')
        ->and($text)->toContain('SMOKE_OK');
})->skip(!gtkExtensionLoaded(), 'ext-gtk is not loaded');
