<?php

declare(strict_types=1);

namespace Jovian\Bindings\Gtk\Values;

final readonly class GdkRGBA
{
    public function __construct(
        public float $red,
        public float $green,
        public float $blue,
        public float $alpha = 1.0,
    ) {
    }

    /**
     * @param array{red?: float|int, green?: float|int, blue?: float|int, alpha?: float|int} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (float) ($data['red'] ?? 0.0),
            (float) ($data['green'] ?? 0.0),
            (float) ($data['blue'] ?? 0.0),
            (float) ($data['alpha'] ?? 1.0),
        );
    }

    /**
     * @return list<float>
     */
    public function toArgs(): array
    {
        return [$this->red, $this->green, $this->blue, $this->alpha];
    }
}
