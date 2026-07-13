<?php
// Smoke test for 10-twig-cms. See SUITE.md "10 — twig-cms" for the
// required markers this checks.
require __DIR__ . '/../../bootstrap.php';

$dir = __DIR__;
$dist = inky_example('10-twig-cms');
$failures = 0;

$cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($dir . '/run.php');
exec($cmd . ' 2>&1', $lines, $exit);
$output = implode("\n", $lines);

if ($exit !== 0) {
    fwrite(STDERR, "10-twig-cms: run.php exited {$exit}\n{$output}\n");
    $failures++;
}

// All six outputs exist.
foreach (['a', 'b'] as $order) {
    for ($n = 1; $n <= 3; $n++) {
        $path = "{$dist}/order-{$order}-{$n}.html";
        if (!is_file($path)) {
            fwrite(STDERR, "10-twig-cms: missing {$path}\n");
            $failures++;
        }
    }
}

// Recipient 1: the correctness claim. Compared with insignificant inter-tag
// whitespace normalized (">\s+<" -> "><") — the same whitespace inky-core's
// own break_long_lines comment calls out as not affecting rendering. See
// run.php's own comment above this same check for the full explanation of
// why a raw byte comparison doesn't hold and what the normalization does
// (and does not) paper over.
$a1 = @file_get_contents($dist . '/order-a-1.html') ?: '';
$b1 = @file_get_contents($dist . '/order-b-1.html') ?: '';
$normalize = static fn (string $html): string => preg_replace('/>\s+</', '><', $html);
if ($a1 === '' || $b1 === '' || $normalize($a1) !== $normalize($b1)) {
    fwrite(STDERR, "10-twig-cms: order-a-1.html and order-b-1.html are not equal (ignoring inter-tag whitespace)\n");
    $failures++;
}

// Timing lines printed.
if (!preg_match('/orderA:\s*[\d.]+\s*ms,\s*orderB:\s*[\d.]+\s*ms \(shell built once\)/', $output)) {
    fwrite(STDERR, "10-twig-cms: expected an 'orderA: X ms, orderB: Y ms (shell built once)' line, got:\n{$output}\n");
    $failures++;
}

// No un-rendered Twig syntax in any final output.
foreach (glob($dist . '/order-*.html') as $path) {
    $html = file_get_contents($path);
    if (str_contains($html, '{{')) {
        fwrite(STDERR, "10-twig-cms: found un-rendered '{{' in " . basename($path) . "\n");
        $failures++;
    }
}

if ($failures > 0) {
    exit(1);
}

echo "10-twig-cms: ok\n";
