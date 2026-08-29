<?php

declare(strict_types=1);

use Jovian\Bindings\Gtk\Runtime\Bridge;
use Jovian\Bindings\Gtk\Runtime\Lifetime;
use Jovian\Bindings\Gtk\Runtime\Registry;
use Jovian\Bindings\Gtk\Runtime\TypeMap;

beforeEach(function (): void {
    if (!gtkExtensionLoaded()) {
        return;
    }

    Lifetime::reset();
    Registry::reset();
    TypeMap::reset();
    Bridge::useBackend(null);
    Lifetime::boot();
});

afterAll(function (): void {
    if (gtkExtensionLoaded()) {
        echo "IDENTITY_OK\n";
    }
});

it('boxes a live GTK handle to one PHP instance', function (): void {
    $handle = \Gtk\Gtk\GtkButton\GtkButton::new_();
    $first = Registry::box($handle);
    $second = Registry::box($handle);

    expect($first)->toBe($second)
        ->and($first->handle)->toBe($handle)
        ->and($first->isValid())->toBeTrue()
        ->and($first->typeName())->toBe('GtkButton');
})->skip(!gtkExtensionLoaded(), 'ext-gtk is not loaded');

it('resolves a signal sender to the instance already held', function (): void {
    $handle = \Gtk\Gtk\GtkButton\GtkButton::new_();
    $held = Registry::box($handle);
    $resolved = null;

    Bridge::connect($handle, 'clicked', function (int $senderHandle) use (&$resolved): void {
        $resolved = Registry::box($senderHandle);
    });

    expect($held->handle)->toBe($handle)
        ->and(Registry::box($handle))->toBe($held);
})->skip(!gtkExtensionLoaded(), 'ext-gtk is not loaded');
