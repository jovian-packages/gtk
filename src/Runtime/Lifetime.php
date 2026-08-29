<?php

declare(strict_types=1);

namespace Jovian\Bindings\Gtk\Runtime;

final class Lifetime
{
    private static bool $booted = false;

    private static bool $shuttingDown = false;

    private static bool $shutdownRegistered = false;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        if (!Bridge::init()) {
            throw new \RuntimeException('Gtk\\Bridge\\Bridge::init() failed. GTK did not initialise.');
        }

        if (class_exists(GeneratedTypeMap::class)) {
            GeneratedTypeMap::install();
        }

        self::$booted = true;

        if (!self::$shutdownRegistered) {
            register_shutdown_function(static function (): void {
                self::$shuttingDown = true;
            });
            self::$shutdownRegistered = true;
        }
    }

    public static function isBooted(): bool
    {
        return self::$booted;
    }

    public static function isShuttingDown(): bool
    {
        return self::$shuttingDown;
    }

    public static function assertBooted(): void
    {
        if (!self::$booted) {
            throw new NotBooted(
                'Jovian GTK is not booted. Call ' . self::class . '::boot() before constructing a GObject.'
            );
        }
    }

    public static function markShuttingDown(): void
    {
        self::$shuttingDown = true;
    }

    public static function reset(): void
    {
        self::$booted = false;
        self::$shuttingDown = false;
    }
}
