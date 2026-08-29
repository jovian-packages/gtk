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
        echo "LIFETIME_OK\n";
    }
});

it('releases the extension registry entry when the PHP object is collected', function (): void {
    $handle = \Gtk\Gtk\GtkButton\GtkButton::new_();
    $object = Registry::box($handle);

    expect(Bridge::isValid($handle))->toBeTrue();

    unset($object);
    gc_collect_cycles();

    expect(Bridge::isValid($handle))->toBeFalse()
        ->and(Registry::find($handle))->toBeNull();
})->skip(!gtkExtensionLoaded(), 'ext-gtk is not loaded');

it('accepts a second boot() after the real Bridge has already initialised', function (): void {
    Lifetime::boot();
    Lifetime::boot();

    expect(Lifetime::isBooted())->toBeTrue();
})->skip(!gtkExtensionLoaded(), 'ext-gtk is not loaded');
