<?php

declare(strict_types=1);

namespace Jovian\Bindings\Gtk\Runtime;

final class TypeMap
{
    /** @var array<string, class-string<GObject>> */
    private static array $classes = [];

    /** @var array<string, list<string>> */
    private static array $lineage = [];

    /**
     * @param class-string<GObject> $class
     */
    public static function register(string $typeName, string $class): void
    {
        self::$classes[$typeName] = $class;
    }

    /**
     * @param list<string> $ancestors most specific first
     */
    public static function lineage(string $typeName, array $ancestors): void
    {
        self::$lineage[$typeName] = $ancestors;
    }

    /**
     * @return class-string<GObject>
     */
    public static function resolve(?string $typeName): string
    {
        if (is_null($typeName) || $typeName === '') {
            return GObject::class;
        }

        if (isset(self::$classes[$typeName])) {
            return self::$classes[$typeName];
        }

        foreach (self::$lineage[$typeName] ?? [] as $ancestor) {
            if (isset(self::$classes[$ancestor])) {
                return self::$classes[$ancestor];
            }
        }

        return GObject::class;
    }

    public static function reset(): void
    {
        self::$classes = [];
        self::$lineage = [];
    }
}
