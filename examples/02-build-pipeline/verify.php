<?php
// Smoke test for 02-build-pipeline. See SUITE.md "02 — build-pipeline" for
// the required markers this checks.
require __DIR__ . '/../../bootstrap.php';

$dist = inky_example('02-build-pipeline');
$path = $dist . '/email.html';

if (!is_file($path)) {
    fwrite(STDERR, "02-build-pipeline: missing {$path} — run examples/02-build-pipeline/run.php first\n");
    exit(1);
}

$html = file_get_contents($path);
$failures = 0;

if (!str_contains($html, '<html')) {
    fwrite(STDERR, "02-build-pipeline: expected <html — the shared layout was not applied\n");
    $failures++;
}

if (!str_contains($html, 'Northwind Coffee')) {
    fwrite(STDERR, "02-build-pipeline: expected the shared header include's wordmark text (\"Northwind Coffee\")\n");
    $failures++;
}

if (!str_contains($html, '#6f4e37')) {
    fwrite(STDERR, "02-build-pipeline: expected the compiled northwind theme color #6f4e37 (linked SCSS was not compiled in)\n");
    $failures++;
}

if ($failures > 0) {
    exit(1);
}

echo "02-build-pipeline: ok\n";
