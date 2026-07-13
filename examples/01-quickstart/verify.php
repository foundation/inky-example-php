<?php
// Smoke test for 01-quickstart. See SUITE.md "01 — quickstart" for the
// required markers this checks.
require __DIR__ . '/../../bootstrap.php';

$dist = inky_example('01-quickstart');
$path = $dist . '/output.html';

if (!is_file($path)) {
    fwrite(STDERR, "01-quickstart: missing {$path} — run examples/01-quickstart/run.php first\n");
    exit(1);
}

$html = file_get_contents($path);
$failures = 0;

if (!str_contains($html, 'class="button"')) {
    fwrite(STDERR, "01-quickstart: expected class=\"button\" in output (transform did not run the button component)\n");
    $failures++;
}

if (str_contains($html, '<button')) {
    fwrite(STDERR, "01-quickstart: found a bare <button> tag — transform should have replaced it with table markup\n");
    $failures++;
}

if ($failures > 0) {
    exit(1);
}

echo "01-quickstart: ok\n";
