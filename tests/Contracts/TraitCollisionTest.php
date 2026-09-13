<?php

declare(strict_types=1);

$package = dirname(__DIR__, 2);
require_once $package . '/scripts/lib/ported.php';
require_once $package . '/scripts/lib/parse.php';
require_once $package . '/scripts/lib/join.php';

it('reports a trait method collision instead of emitting uncompilable code', function (): void {
    $classes = [
        'Gtk\\IfaceA' => [
            'kind' => 'interface',
            'cType' => 'GtkIfaceA',
            'girName' => 'IfaceA',
            'girNs' => 'Gtk',
            'methods' => [['extMethod' => 'shared']],
        ],
        'Gtk\\IfaceB' => [
            'kind' => 'interface',
            'cType' => 'GtkIfaceB',
            'girName' => 'IfaceB',
            'girNs' => 'Gtk',
            'methods' => [['extMethod' => 'shared']],
        ],
        'Gtk\\GtkWidget' => [
            'kind' => 'class',
            'implements' => ['IfaceA', 'IfaceB'],
            'methods' => [],
        ],
    ];

    $collisions = detectInterfaceTraitCollisions($classes);
    expect($collisions)->not->toBeEmpty()
        ->and($collisions[0])->toContain('trait collision on shared');
});

it('finds no trait collisions among the bound GTK interfaces', function (): void {
    $ext = gtkExtRoot();
    $parsed = parseBoundMethods($ext);
    $joined = joinAnnotationsToGir($parsed['methods'], $ext . '/scripts/gir');

    expect(detectInterfaceTraitCollisions($joined['classes']))->toBe([]);
});
