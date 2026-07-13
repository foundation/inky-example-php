<?php
// Smoke test for 09-transactional. See SUITE.md "09 — transactional
// (capstone)" for the required markers this checks. Runs run.php TWICE as
// a subprocess (mirroring the 06-validate-gate pattern) so the second
// invocation observes a warm EmailRenderer cache, even on a clean checkout
// where the first-ever invocation (here or from build.php) was a miss.
require __DIR__ . '/../../bootstrap.php';

$dir = __DIR__;
$dist = inky_example('09-transactional');
$failures = 0;

function run_capstone(string $dir): array
{
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($dir . '/run.php');
    exec($cmd . ' 2>&1', $lines, $exit);
    return [$exit, implode("\n", $lines)];
}

[$firstExit, $firstOutput] = run_capstone($dir);
[$secondExit, $secondOutput] = run_capstone($dir);

if ($firstExit !== 0 || $secondExit !== 0) {
    fwrite(STDERR, "09-transactional: run.php exited non-zero (first={$firstExit}, second={$secondExit})\n{$secondOutput}\n");
    $failures++;
}

$hitCount = substr_count($secondOutput, 'hit (served from cache)');
if ($hitCount !== 3) {
    fwrite(STDERR, "09-transactional: expected 3 cache hits on the second run.php invocation, found {$hitCount}\n{$secondOutput}\n");
    $failures++;
}

if (!preg_match('/total warnings:\s*(\d+)/', $secondOutput, $m) || (int) $m[1] !== 0) {
    fwrite(STDERR, "09-transactional: expected zero warnings across all three templates, output:\n{$secondOutput}\n");
    $failures++;
}

// Six output files: three emails, each .html + .txt.
foreach (['welcome', 'receipt', 'password-reset'] as $name) {
    foreach (['html', 'txt'] as $ext) {
        $path = "{$dist}/{$name}.{$ext}";
        if (!is_file($path)) {
            fwrite(STDERR, "09-transactional: missing {$path}\n");
            $failures++;
        }
    }
}

// Receipt totals row: a "$"-amount inside the totals row specifically.
$receiptHtml = @file_get_contents($dist . '/receipt.html') ?: '';
if (!preg_match('/<tr class="totals-row"[^>]*>.*?<\/tr>/s', $receiptHtml, $m) || !preg_match('/\$[0-9]/', $m[0])) {
    fwrite(STDERR, "09-transactional: expected a \$-amount inside <tr class=\"totals-row\">...</tr> in receipt.html\n");
    $failures++;
}

if ($failures > 0) {
    exit(1);
}

echo "09-transactional: ok\n";
