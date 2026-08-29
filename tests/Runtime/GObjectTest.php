<?php

declare(strict_types=1);

use DeptOfScrapyardRobotics\Tests\Runtime\FakeBridge;
use Jovian\Bindings\Gtk\Runtime\GObject;
use Jovian\Bindings\Gtk\Runtime\Registry;

beforeEach(function (): void {
    bootFakeRuntime();
});

it('answers typeName, isValid, and isA from the Bridge, never from cached PHP state', function (): void {
    $object = Registry::box(3);
    FakeBridge::$types[3] = 'GtkButton';
    FakeBridge::$valid[3] = true;
    FakeBridge::$isA[3] = ['GtkButton', 'GtkWidget', 'GObject'];

    expect($object->typeName())->toBe('GtkButton')
        ->and($object->isValid())->toBeTrue()
        ->and($object->isA('GtkWidget'))->toBeTrue();

    FakeBridge::$types[3] = 'GtkLabel';
    FakeBridge::$valid[3] = false;
    FakeBridge::$isA[3] = ['GtkLabel', 'GtkWidget', 'GObject'];

    expect($object->typeName())->toBe('GtkLabel')
        ->and($object->isValid())->toBeFalse()
        ->and($object->isA('GtkButton'))->toBeFalse()
        ->and($object->isA('GtkLabel'))->toBeTrue();
});

it('reads and writes properties through the Bridge', function (): void {
    $object = Registry::box(4);
    FakeBridge::$properties[4]['title'] = 'Hello';

    expect($object->getProperty('title'))->toBe('Hello');

    $fluent = $object->setProperty('title', 'World');

    expect($fluent)->toBe($object)
        ->and($object->getProperty('title'))->toBe('World');
});
