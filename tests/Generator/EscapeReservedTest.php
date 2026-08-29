<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/scripts/lib/ported.php';

it('replays escapeReserved so new() is the extension-visible new_()', function (): void {
    expect(escapeReserved('new'))->toBe('new_')
        ->and(escapeReserved('new_'))->toBe('new_')
        ->and(escapeReserved('setLabel'))->toBe('setLabel')
        ->and(escapeReserved('object'))->toBe('object_');
});

it('lowercases an all-caps Zephir constant token the way gen-zep.php does', function (): void {
    expect(escapeReserved('UTF8'))->toBe('Utf8')
        ->and(escapeReserved('A'))->toBe('a');
});
