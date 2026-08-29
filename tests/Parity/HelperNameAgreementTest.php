<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/scripts/lib/helper-path.php';
require_once dirname(__DIR__, 2) . '/.unlazy/jovian-gtk/scripts/lib/jovian-autoload.php';

it('keeps the helper-name rule in one shared PHP source', function (): void {
    $root = dirname(__DIR__, 2);
    expect(is_file($root . '/scripts/lib/helper-path.php'))->toBeTrue();

    $emit = (string) file_get_contents($root . '/scripts/lib/emit.php');
    expect($emit)->toContain('helper-path.php')
        ->and($emit)->not->toContain("preg_replace('/([A-Z]+)([A-Z][a-z])/'");
});

it('names G-prefixed Gio helpers with the acronym split, not the naive kebab', function (): void {
    expect(helperRelPath('Gio\\GApplication'))->toBe('src/Helpers/g-application.php')
        ->and(helperRelPath('Gio\\GListStore'))->toBe('src/Helpers/g-list-store.php')
        ->and(helperRelPath('Gio\\GSimpleAction'))->toBe('src/Helpers/g-simple-action.php');
});

it('emits a helper file at the shared path for every generated class', function (): void {
    $root = dirname(__DIR__, 2);

    foreach (jovianGeneratedClassPaths($root) as $row) {
        $rel = helperRelPath($row['classPath']);
        expect(is_file($root . '/' . $rel))->toBeTrue("missing helper {$rel} for {$row['classPath']}");
    }
});
