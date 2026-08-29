<?php

declare(strict_types=1);

use DeptOfScrapyardRobotics\Tests\Runtime\FakeBridge;
use Jovian\Bindings\Gtk\Runtime\Bridge;
use Jovian\Bindings\Gtk\Runtime\GObject;
use Jovian\Bindings\Gtk\Runtime\Lifetime;
use Jovian\Bindings\Gtk\Runtime\NotBooted;
use Jovian\Bindings\Gtk\Runtime\Registry;

beforeEach(function (): void {
    bootFakeRuntime();
});

it('retains on construction and releases when the last PHP reference drops', function (): void {
    $object = Registry::box(5);

    expect(FakeBridge::$retains)->toBe([5]);

    unset($object);
    gc_collect_cycles();

    expect(FakeBridge::$releases)->toBe([5])
        ->and(Registry::find(5))->toBeNull();
});

it('does not evict or release a recycled handle that now names a live object', function (): void {
    $dying = new GObject(42);
    $live = new GObject(42);

    expect(Registry::find(42))->toBe($live);

    unset($dying);
    gc_collect_cycles();

    expect(Registry::find(42))->toBe($live)
        ->and(FakeBridge::$releases)->toBe([])
        ->and($live->handle)->toBe(42);
});

it('skips release after the shutdown flag is set', function (): void {
    $object = Registry::box(9);

    Lifetime::markShuttingDown();
    unset($object);
    gc_collect_cycles();

    expect(FakeBridge::$releases)->toBe([])
        ->and(Lifetime::isShuttingDown())->toBeTrue();
});

it('treats a second boot() as a no-op', function (): void {
    expect(FakeBridge::$initCalls)->toBe(1);

    Lifetime::boot();
    Lifetime::boot();

    expect(FakeBridge::$initCalls)->toBe(1)
        ->and(Lifetime::isBooted())->toBeTrue();
});

it('throws a clear error when a GObject is constructed before boot', function (): void {
    Lifetime::reset();

    expect(fn (): GObject => new GObject(1))->toThrow(NotBooted::class);
});

it('throws when boxing a handle before boot', function (): void {
    Lifetime::reset();

    expect(fn (): ?GObject => Registry::box(3))->toThrow(NotBooted::class);
});

it('throws when Bridge::init() fails', function (): void {
    Lifetime::reset();
    FakeBridge::reset();
    FakeBridge::$initResult = false;
    Bridge::useBackend(FakeBridge::class);

    expect(function (): void {
        Lifetime::boot();
    })->toThrow(\RuntimeException::class);
    expect(Lifetime::isBooted())->toBeFalse();
});
