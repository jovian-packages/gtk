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

function gtkExtRoot(): string
{
    $package = dirname(__DIR__);
    $candidates = [];
    $env = getenv('JOVIAN_GTK_EXT');
    if (is_string($env) && $env !== '') {
        $candidates[] = $env;
    }
    $candidates[] = $package . '/../../php-io-extensions/gtk';
    $candidates[] = $package . '/../php-io-extensions/gtk';
    $candidates[] = '/home/angel/gtk';
    $candidates[] = '/home/angel/Development/PHP/php-io-extensions/gtk';

    foreach ($candidates as $candidate) {
        if (is_dir($candidate . '/scripts/gir')) {
            return $candidate;
        }
    }

    throw new RuntimeException('ext-gtk checkout with scripts/gir not found');
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
