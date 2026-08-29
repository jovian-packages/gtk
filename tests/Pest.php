<?php

declare(strict_types=1);

use DeptOfScrapyardRobotics\Tests\Runtime\FakeBridge;
use Jovian\Bindings\Gtk\Runtime\Bridge;
use Jovian\Bindings\Gtk\Runtime\Lifetime;
use Jovian\Bindings\Gtk\Runtime\Registry;
use Jovian\Bindings\Gtk\Runtime\TypeMap;

function gtkExtensionLoaded(): bool
{
    return extension_loaded('gtk');
}

function bootFakeRuntime(): void
{
    Lifetime::reset();
    Registry::reset();
    TypeMap::reset();
    FakeBridge::reset();
    Bridge::useBackend(FakeBridge::class);
    Lifetime::boot();
}

afterEach(function (): void {
    if (!class_exists(Lifetime::class)) {
        return;
    }

    Lifetime::reset();
    Registry::reset();
    TypeMap::reset();
    FakeBridge::reset();
    Bridge::useBackend(FakeBridge::class);
});
