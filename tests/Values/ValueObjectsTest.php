<?php

declare(strict_types=1);

use Jovian\Bindings\Gtk\Values\GdkRGBA;
use Jovian\Bindings\Gtk\Values\GdkRectangle;
use Jovian\Bindings\Gtk\Values\GraphenePoint;
use Jovian\Bindings\Gtk\Values\GrapheneRect;

it('round-trips GdkRGBA through the extension assoc-array ABI', function (): void {
    $color = GdkRGBA::fromArray([
        'red' => 1.0,
        'green' => 0.5,
        'blue' => 0.25,
        'alpha' => 0.75,
    ]);

    expect($color->red)->toBe(1.0)
        ->and($color->green)->toBe(0.5)
        ->and($color->blue)->toBe(0.25)
        ->and($color->alpha)->toBe(0.75)
        ->and($color->toArgs())->toBe([1.0, 0.5, 0.25, 0.75]);
});

it('defaults missing GdkRGBA channels to zero, except opaque alpha', function (): void {
    $color = GdkRGBA::fromArray([]);

    expect($color->toArgs())->toBe([0.0, 0.0, 0.0, 1.0]);
});

it('round-trips GdkRectangle as ints out and component doubles in', function (): void {
    $rect = GdkRectangle::fromArray([
        'x' => 8,
        'y' => 16,
        'width' => 320,
        'height' => 240,
    ]);

    expect($rect->x)->toBe(8)
        ->and($rect->y)->toBe(16)
        ->and($rect->width)->toBe(320)
        ->and($rect->height)->toBe(240)
        ->and($rect->toArgs())->toBe([8.0, 16.0, 320.0, 240.0]);
});

it('round-trips graphene rects and points as component doubles', function (): void {
    $rect = GrapheneRect::fromArray([
        'x' => 1.5,
        'y' => 2.5,
        'width' => 10.0,
        'height' => 20.0,
    ]);
    $point = GraphenePoint::fromArray([
        'x' => 3.25,
        'y' => 4.75,
    ]);

    expect($rect->toArgs())->toBe([1.5, 2.5, 10.0, 20.0])
        ->and($point->toArgs())->toBe([3.25, 4.75]);
});
