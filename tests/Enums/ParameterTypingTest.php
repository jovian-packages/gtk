<?php

declare(strict_types=1);

it('types enumeration parameters as EnumName|int and unwraps the case value', function (): void {
    $box = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Gtk/GtkBox.php');
    expect($box)->toContain('GtkOrientation|int $orientation')
        ->and($box)->toContain('($orientation instanceof GtkOrientation ? $orientation->value : $orientation)');

    $orientable = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Gtk/GtkOrientable.php');
    expect($orientable)->toContain('GtkOrientation|int $orientation')
        ->and($orientable)->toContain('($orientation instanceof GtkOrientation ? $orientation->value : $orientation)');
});

it('keeps bitfield parameters as int because PHP enums cannot be OR\'d', function (): void {
    $widget = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Gtk/GtkWidget.php');
    expect($widget)->toMatch('/function setStateFlags\(int \$flags/')
        ->and($widget)->not->toContain('GtkStateFlags|int');

    $helper = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Helpers/gtk-box.php');
    expect($helper)->toContain('function gtk_box_new(int $orientation, int $spacing): int');
});
