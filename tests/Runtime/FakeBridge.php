<?php

declare(strict_types=1);

namespace DeptOfScrapyardRobotics\Tests\Runtime;

final class FakeBridge
{
    public static int $initCalls = 0;

    public static bool $initResult = true;

    /** @var list<int> */
    public static array $retains = [];

    /** @var list<int> */
    public static array $releases = [];

    /** @var array<int, string|null> */
    public static array $types = [];

    /** @var array<int, bool> */
    public static array $valid = [];

    /** @var array<int, list<string>> */
    public static array $isA = [];

    /** @var array<int, array<string, mixed>> */
    public static array $properties = [];

    public static function reset(): void
    {
        self::$initCalls = 0;
        self::$initResult = true;
        self::$retains = [];
        self::$releases = [];
        self::$types = [];
        self::$valid = [];
        self::$isA = [];
        self::$properties = [];
    }

    public static function init(): bool
    {
        self::$initCalls++;

        return self::$initResult;
    }

    public static function retain(int $handle): bool
    {
        self::$retains[] = $handle;

        return self::$valid[$handle] ?? $handle > 0;
    }

    public static function release(int $handle): void
    {
        self::$releases[] = $handle;
    }

    public static function isValid(int $handle): bool
    {
        return self::$valid[$handle] ?? $handle > 0;
    }

    public static function typeName(int $handle): mixed
    {
        if (array_key_exists($handle, self::$types)) {
            return self::$types[$handle];
        }

        return 'GObject';
    }

    public static function isA(int $handle, string $typeName): bool
    {
        if (isset(self::$isA[$handle])) {
            return in_array($typeName, self::$isA[$handle], true);
        }

        $actual = self::typeName($handle);

        return $actual === $typeName || $typeName === 'GObject';
    }

    public static function typeFromName(string $typeName): int
    {
        return $typeName === '' ? 0 : 1;
    }

    public static function pump(int $timeoutMs): int
    {
        return $timeoutMs > 0 ? 1 : 0;
    }

    public static function connect(int $handle, string $signal, mixed $callback): int
    {
        return $handle > 0 && $signal !== '' && is_callable($callback) ? 1 : 0;
    }

    public static function disconnect(int $handle, int $handlerId): void
    {
    }

    public static function getProperty(int $handle, string $name): mixed
    {
        return self::$properties[$handle][$name] ?? null;
    }

    public static function setProperty(int $handle, string $name, mixed $value): void
    {
        self::$properties[$handle][$name] = $value;
    }
}
