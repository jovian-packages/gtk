<?php

declare(strict_types=1);

namespace Jovian\Bindings\Gtk\Runtime;

class GObject
{
    public function __construct(public readonly int $handle)
    {
        Lifetime::assertBooted();

        if ($handle <= 0) {
            throw new \InvalidArgumentException('GObject handle must be a positive registry integer.');
        }

        Bridge::retain($handle);
        Registry::remember($this);
    }

    public function __destruct()
    {
        if (Lifetime::isShuttingDown()) {
            return;
        }

        $current = Registry::find($this->handle);
        if ($current !== $this) {
            return;
        }

        Registry::forget($this->handle);
        Bridge::release($this->handle);
    }

    public function typeName(): mixed
    {
        return Bridge::typeName($this->handle);
    }

    public function isValid(): bool
    {
        return Bridge::isValid($this->handle);
    }

    public function isA(string $gtype): bool
    {
        return Bridge::isA($this->handle, $gtype);
    }

    public function getProperty(string $name): mixed
    {
        return Bridge::getProperty($this->handle, $name);
    }

    public function setProperty(string $name, mixed $value): static
    {
        Bridge::setProperty($this->handle, $name, $value);

        return $this;
    }
}
