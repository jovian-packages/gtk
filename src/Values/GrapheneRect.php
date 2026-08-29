<?php

declare(strict_types=1);

namespace Jovian\Bindings\Gtk\Values;

final readonly class GrapheneRect
{
    public function __construct(
        public float $x,
        public float $y,
        public float $width,
        public float $height,
    ) {
    }

    /**
     * @param array{x?: float|int, y?: float|int, width?: float|int, height?: float|int} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (float) ($data['x'] ?? 0.0),
            (float) ($data['y'] ?? 0.0),
            (float) ($data['width'] ?? 0.0),
            (float) ($data['height'] ?? 0.0),
        );
    }

    /**
     * @return list<float>
     */
    public function toArgs(): array
    {
        return [$this->x, $this->y, $this->width, $this->height];
    }
}
