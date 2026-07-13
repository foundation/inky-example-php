<?php
// Smoke test for 06-validate-gate. See SUITE.md "06 — validate-gate" for
// the required markers this checks. Runs run.php as a subprocess with
// explicit argv paths so it observes the REAL gate exit codes — the
// no-args path is a demo only and always exits 0 (see run.php's header).
require __DIR__ . '/../../bootstrap.php';

$dir = __DIR__;
$failures = 0;

function run_gate(string $dir, string ...$paths): array
{
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($dir . '/run.php');
    foreach ($paths as $path) {
        $cmd .= ' ' . escapeshellarg($path);
    }
    exec($cmd . ' 2>&1', $lines, $exit);
    return [$exit, implode("\n", $lines)];
}

// bad.inky alone -> exit 1, with distinct rule ids present in the output.
[$badExit, $badOutput] = run_gate($dir, $dir . '/bad.inky');

if ($badExit !== 1) {
    fwrite(STDERR, "06-validate-gate: expected exit 1 for bad.inky, got {$badExit}\n{$badOutput}\n");
    $failures++;
}
if (!str_contains($badOutput, 'button-no-href')) {
    fwrite(STDERR, "06-validate-gate: expected rule id 'button-no-href' in output\n");
    $failures++;
}
if (!str_contains($badOutput, 'missing-preheader')) {
    fwrite(STDERR, "06-validate-gate: expected rule id 'missing-preheader' in output\n");
    $failures++;
}

// good.inky alone -> exit 0.
[$goodExit, $goodOutput] = run_gate($dir, $dir . '/good.inky');

if ($goodExit !== 0) {
    fwrite(STDERR, "06-validate-gate: expected exit 0 for good.inky, got {$goodExit}\n{$goodOutput}\n");
    $failures++;
}

if ($failures > 0) {
    exit(1);
}

echo "06-validate-gate: ok\n";
