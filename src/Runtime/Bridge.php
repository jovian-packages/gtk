<?php

declare(strict_types=1);

namespace Jovian\Bindings\Gtk\Runtime;

use Gtk\Bridge\Bridge as ExtBridge;

final class Bridge
{
    private static ?string $backend = null;

    public static function useBackend(?string $backend): void
    {
        self::$backend = $backend;
    }

    public static function init(): bool
    {
        return self::backend()::init();
    }

    public static function retain(int $handle): bool
    {
        return self::backend()::retain($handle);
    }

    public static function release(int $handle): void
    {
        self::backend()::release($handle);
    }

    public static function isValid(int $handle): bool
    {
        return self::backend()::isValid($handle);
    }

    public static function typeName(int $handle): mixed
    {
        return self::backend()::typeName($handle);
    }

    public static function isA(int $handle, string $typeName): bool
    {
        return self::backend()::isA($handle, $typeName);
    }

    public static function typeFromName(string $typeName): int
    {
        return self::backend()::typeFromName($typeName);
    }

    public static function pump(int $timeoutMs): int
    {
        return self::backend()::pump($timeoutMs);
    }

    public static function connect(int $handle, string $signal, mixed $callback): int
    {
        return self::backend()::connect($handle, $signal, $callback);
    }

    public static function disconnect(int $handle, int $handlerId): void
    {
        self::backend()::disconnect($handle, $handlerId);
    }

    public static function getProperty(int $handle, string $name): mixed
    {
        return self::backend()::getProperty($handle, $name);
    }

    public static function setProperty(int $handle, string $name, mixed $value): void
    {
        self::backend()::setProperty($handle, $name, $value);
    }

    private static function backend(): string
    {
        if (!is_null(self::$backend)) {
            return self::$backend;
        }

        return ExtBridge::class;
    }
}
