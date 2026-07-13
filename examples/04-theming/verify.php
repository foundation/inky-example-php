<?php
// Smoke test for 04-theming. See SUITE.md "04 — theming" for the required
// markers this checks.
require __DIR__ . '/../../bootstrap.php';

$dist = inky_example('04-theming');
$northwindPath = $dist . '/promo-northwind.html';
$midnightPath = $dist . '/promo-midnight.html';

if (!is_file($northwindPath) || !is_file($midnightPath)) {
    fwrite(STDERR, "04-theming: missing promo-northwind.html and/or promo-midnight.html — run examples/04-theming/run.php first\n");
    exit(1);
}

$northwind = file_get_contents($northwindPath);
$midnight = file_get_contents($midnightPath);
$failures = 0;

if (!str_contains($northwind, '#6f4e37')) {
    fwrite(STDERR, "04-theming: expected the northwind theme color #6f4e37 in promo-northwind.html\n");
    $failures++;
}

if (!str_contains($midnight, '#4a6cf7')) {
    fwrite(STDERR, "04-theming: expected the midnight theme color #4a6cf7 in promo-midnight.html\n");
    $failures++;
}

if ($northwind === $midnight) {
    fwrite(STDERR, "04-theming: promo-northwind.html and promo-midnight.html are byte-identical — the theme swap did not take effect\n");
    $failures++;
}

if ($failures > 0) {
    exit(1);
}

echo "04-theming: ok\n";
