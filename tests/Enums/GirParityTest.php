<?php

declare(strict_types=1);

$package = dirname(__DIR__, 2);
require_once $package . '/scripts/lib/ported.php';
require_once $package . '/scripts/lib/parse.php';
require_once $package . '/scripts/lib/join.php';
require_once $package . '/scripts/lib/emit.php';
require_once $package . '/scripts/lib/enums.php';

it('re-reads the vendored GIR and matches every generated enum case value', function (): void {
    $index = indexGirEnums(gtkExtRoot() . '/scripts/gir');
    $files = glob(dirname(__DIR__, 2) . '/src/Enums/*.php') ?: [];
    expect($files)->not->toBeEmpty();

    $compared = 0;
    foreach ($files as $file) {
        $source = (string) file_get_contents($file);
        expect($source)->toMatch('/enum\s+(\w+)\s*:\s*int/');
        preg_match('/enum\s+(\w+)\s*:\s*int/', $source, $header);
        $cType = $header[1];
        expect($index)->toHaveKey($cType);
        $record = $index[$cType];

        preg_match_all('/case\s+([A-Z][A-Z0-9_]*)\s*=\s*(-?\d+);/', $source, $matches, PREG_SET_ORDER);
        $byCase = [];
        foreach ($matches as $match) {
            $byCase[$match[1]] = (int) $match[2];
        }
        expect($byCase)->not->toBeEmpty();

        $seenValues = [];
        foreach ($record['members'] as $member) {
            $case = girMemberCaseName($member['name']);
            if (isset($seenValues[$member['value']])) {
                continue;
            }
            $seenValues[$member['value']] = $case;
            expect($byCase)->toHaveKey($case)
                ->and($byCase[$case])->toBe($member['value']);
            $compared++;
        }
        foreach ($byCase as $case => $value) {
            $girValue = null;
            foreach ($record['members'] as $member) {
                if (girMemberCaseName($member['name']) === $case) {
                    $girValue = $member['value'];
                    break;
                }
            }
            expect($girValue)->not->toBeNull()
                ->and($value)->toBe($girValue);
        }
    }

    expect($compared)->toBeGreaterThan(0);
});

it('generates only enumerations and bitfields reachable from a bound signature', function (): void {
    $ext = gtkExtRoot();
    $parsed = parseBoundMethods($ext);
    $joined = joinAnnotationsToGir($parsed['methods'], $ext . '/scripts/gir');
    $reachable = collectReachableEnums($joined['classes'], $ext . '/scripts/gir');

    $generated = [];
    foreach (glob(dirname(__DIR__, 2) . '/src/Enums/*.php') ?: [] as $file) {
        $generated[] = basename($file, '.php');
    }
    sort($generated);
    $expected = array_keys($reachable);
    sort($expected);

    expect($generated)->toBe($expected);
});
