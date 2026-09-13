<?php

declare(strict_types=1);

it('hard-fails when an annotation has no GIR member and does not skip it', function (): void {
    $ext = gtkExtRoot();

    $tmp = sys_get_temp_dir() . '/jovian-gtk-unmatched-' . bin2hex(random_bytes(4));
    mkdir($tmp . '/src', 0755, true);
    mkdir($tmp . '/scripts', 0755, true);
    symlink($ext . '/scripts/gir', $tmp . '/scripts/gir');

    file_put_contents($tmp . '/src/fake.h', <<<'H'
/*@zep Gtk\GtkButton totallyFake(int handle) -> void */
void phpgtk_gtkbutton_totally_fake(zval *handle);
H);

    $generate = dirname(__DIR__, 2) . '/scripts/generate.php';
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($generate) . ' --ext=' . escapeshellarg($tmp);
    exec($command . ' 2>&1', $output, $status);

    $text = implode("\n", $output);

    expect($status)->not->toBe(0)
        ->and($text)->toContain('unmatched')
        ->and($text)->not->toContain('GEN_OK');
});
