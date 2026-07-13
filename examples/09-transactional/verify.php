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

// EmailRenderer::render()'s cache-hit path returns \Inky\BuildResult with an
// empty $warnings array unconditionally (see src/EmailRenderer.php) — it
// never re-runs Inky::build() on a hit, so it has no warnings to report,
// not because the template is actually warning-clean. Asserting "zero
// warnings" against a run that's all cache hits would therefore pass even
// if the templates were full of warnings, as long as a prior run had ever
// cached them. So: clear the cache first, run once COLD and assert zero
// warnings from THAT run (the only run that actually asks Inky::build() to
// check), then run again and confirm the second run is all cache hits.
$cacheDir = $dir . '/cache';
foreach (glob($cacheDir . '/*.json') ?: [] as $cachedFile) {
    unlink($cachedFile);
}

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

// Checked against the FIRST (cold) run's output, not the second (warm) run
// — a warm run always reports zero warnings regardless of template health
// (see the comment above), so it can't tell us anything about whether the
// templates are actually warning-clean.
if (!preg_match('/total warnings:\s*(\d+)/', $firstOutput, $m) || (int) $m[1] !== 0) {
    fwrite(STDERR, "09-transactional: expected zero warnings across all three templates on the cold run, output:\n{$firstOutput}\n");
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
