<?php

declare(strict_types=1);

namespace Jovian\Bindings\Gtk\Runtime;

use WeakReference;

final class Registry
{
    /** @var array<int, WeakReference<GObject>> */
    private static array $map = [];

    public static function box(int $handle): ?GObject
    {
        Lifetime::assertBooted();

        if ($handle <= 0) {
            return null;
        }

        $existing = self::find($handle);
        if (!is_null($existing)) {
            return $existing;
        }

        $typeName = Bridge::typeName($handle);
        $class = TypeMap::resolveWithProbe(is_string($typeName) ? $typeName : null, $handle);

        return new $class($handle);
    }

    public static function find(int $handle): ?GObject
    {
        if (!isset(self::$map[$handle])) {
            return null;
        }

        $object = self::$map[$handle]->get();
        if (is_null($object)) {
            unset(self::$map[$handle]);

            return null;
        }

        return $object;
    }

    public static function remember(GObject $object): void
    {
        self::$map[$object->handle] = WeakReference::create($object);
    }

    public static function forget(int $handle): void
    {
        unset(self::$map[$handle]);
    }

    public static function reset(): void
    {
        self::$map = [];
    }
}
