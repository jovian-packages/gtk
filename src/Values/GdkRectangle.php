<?php

declare(strict_types=1);

namespace Jovian\Bindings\Gtk\Values;

final readonly class GdkRectangle
{
    public function __construct(
        public int $x,
        public int $y,
        public int $width,
        public int $height,
    ) {
    }

    /**
     * @param array{x?: int|float, y?: int|float, width?: int|float, height?: int|float} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['x'] ?? 0),
            (int) ($data['y'] ?? 0),
            (int) ($data['width'] ?? 0),
            (int) ($data['height'] ?? 0),
        );
    }

    /**
     * Component doubles in, matching phpgtk_arg_double on GdkRectangle parameters.
     *
     * @return list<float>
     */
    public function toArgs(): array
    {
        return [(float) $this->x, (float) $this->y, (float) $this->width, (float) $this->height];
    }
}
