<?php

declare(strict_types=1);

it('widens GtkApplicationWindow::new() so it is compatible with GtkWindow::new()', function (): void {
    $root = dirname(__DIR__, 2);
    $source = (string) file_get_contents($root . '/src/Gtk/GtkApplicationWindow.php');

    expect($source)->toContain('public static function new(?GtkApplication $application = null): self')
        ->and($source)->toContain('is_null($application)')
        ->and($source)->not->toContain('public static function new(GtkApplication $application): self');
});
