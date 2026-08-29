<?php

declare(strict_types=1);

use DeptOfScrapyardRobotics\Tests\Runtime\FakeBridge;
use DeptOfScrapyardRobotics\Tests\Runtime\IdentityWidget;
use Jovian\Bindings\Gtk\Runtime\GObject;
use Jovian\Bindings\Gtk\Runtime\Registry;
use Jovian\Bindings\Gtk\Runtime\TypeMap;

beforeEach(function (): void {
    bootFakeRuntime();
});

it('boxes the same handle to the identical PHP instance', function (): void {
    $first = Registry::box(7);
    $second = Registry::box(7);

    expect($first)->toBeInstanceOf(GObject::class)
        ->and($second)->toBe($first)
        ->and($first->handle)->toBe(7);
});

it('returns null when boxing a NULL handle', function (): void {
    expect(Registry::box(0))->toBeNull();
});

it('resolves a signal sender handle to the instance already held', function (): void {
    $held = Registry::box(21);
    $resolved = null;

    $onClicked = function (int $senderHandle) use (&$resolved): void {
        $resolved = Registry::box($senderHandle);
    };
    $onClicked(21);

    expect($resolved)->toBe($held);
});

it('classes a first-seen handle from Bridge::typeName through the type map', function (): void {
    TypeMap::register('GtkWidget', IdentityWidget::class);
    FakeBridge::$types[11] = 'GtkWidget';

    $boxed = Registry::box(11);

    expect($boxed)->toBeInstanceOf(IdentityWidget::class)
        ->and($boxed->handle)->toBe(11);
});

it('falls back to the nearest bound ancestor then GObject', function (): void {
    TypeMap::reset();
    TypeMap::register('GtkWidget', IdentityWidget::class);
    TypeMap::lineage('GtkButton', ['GtkWidget', 'GInitiallyUnowned', 'GObject']);
    FakeBridge::$types[13] = 'GtkButton';

    expect(Registry::box(13))->toBeInstanceOf(IdentityWidget::class);
});

it('falls back to GObject when the GType is unbound', function (): void {
    FakeBridge::$types[14] = 'GtkImaginary';

    expect(Registry::box(14))->toBeInstanceOf(GObject::class);
});
