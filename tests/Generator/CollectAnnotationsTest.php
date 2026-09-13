<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/scripts/lib/ported.php';

it('collects @zep, @reserved, and construction paths from the extension headers', function (): void {
    $ext = gtkExtRoot();

    $annotations = collectAnnotations($ext);

    expect($annotations['classes'])->toHaveKey('Gtk\\GtkButton')
        ->and($annotations['classes']['Gtk\\GtkButton']['bound'])->toBeGreaterThan(0)
        ->and($annotations['classes']['Gtk\\GtkButton']['hasConstruction'])->toBeTrue()
        ->and($annotations['classes'])->toHaveKey('Bridge\\Bridge');
});

it('names the OBTAIN_ONLY classes the extension audit exempts', function (): void {
    expect(OBTAIN_ONLY)->toContain('Gtk\\GtkSettings')
        ->and(OBTAIN_ONLY)->toContain('Gdk\\GdkDisplay')
        ->and(OBTAIN_ONLY)->toContain('Gtk\\GtkRange')
        ->and(OBTAIN_ONLY)->toContain('Gtk\\GtkNotebookPage')
        ->and(OBTAIN_ONLY)->toContain('Gtk\\GtkStackPage');
});
