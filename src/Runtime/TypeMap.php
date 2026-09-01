<?php

declare(strict_types=1);

namespace Jovian\Bindings\Gtk\Runtime;

final class TypeMap
{
    /** @var array<string, class-string<GObject>> */
    private static array $classes = [];

    /** @var array<string, list<string>> */
    private static array $lineage = [];

    /** @var array<string, class-string<GObject>> unknown GType name → probed verdict */
    private static array $probed = [];

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

    /**
     * Resolve a GType name the map has never heard of by asking GObject
     * itself. Media backends and theme engines hand out runtime
     * subclasses (GtkGstMediaFile for a GtkMediaFile) whose names appear
     * in no generated table; Bridge::isA walks the real type hierarchy,
     * and the deepest registered ancestor wins. The verdict is cached so
     * each unknown GType pays for one scan.
     *
     * @return class-string<GObject>
     */
    public static function resolveWithProbe(?string $typeName, int $handle): string
    {
        $resolved = self::resolve($typeName);
        if ($resolved !== GObject::class || is_null($typeName) || $typeName === '') {
            return $resolved;
        }

        if (isset(self::$probed[$typeName])) {
            return self::$probed[$typeName];
        }

        $best = GObject::class;
        $bestDepth = -1;
        foreach (self::$classes as $candidate => $class) {
            if (! Bridge::isA($handle, $candidate)) {
                continue;
            }
            $depth = count(self::$lineage[$candidate] ?? []);
            if ($depth > $bestDepth) {
                $best = $class;
                $bestDepth = $depth;
            }
        }

        return self::$probed[$typeName] = $best;
    }

    public static function reset(): void
    {
        self::$classes = [];
        self::$lineage = [];
        self::$probed = [];
    }
}
