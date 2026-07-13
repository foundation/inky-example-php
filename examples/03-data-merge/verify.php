<?php
// Smoke test for 03-data-merge. See SUITE.md "03 — data-merge" for the
// required markers this checks.
require __DIR__ . '/../../bootstrap.php';

$dist = inky_example('03-data-merge');
$path = $dist . '/order.html';

if (!is_file($path)) {
    fwrite(STDERR, "03-data-merge: missing {$path} — run examples/03-data-merge/run.php first\n");
    exit(1);
}

$html = file_get_contents($path);
$failures = 0;

if (!str_contains($html, 'NW-10482')) {
    fwrite(STDERR, "03-data-merge: expected the order number NW-10482 (customer/order variables did not merge)\n");
    $failures++;
}

$rowCount = substr_count($html, '<tr class="line-item"');
if ($rowCount !== 3) {
    fwrite(STDERR, "03-data-merge: expected exactly 3 line-item rows, found {$rowCount}\n");
    $failures++;
}

if (str_contains($html, '{%')) {
    fwrite(STDERR, "03-data-merge: found un-merged '{%' template syntax in output\n");
    $failures++;
}

if ($failures > 0) {
    exit(1);
}

echo "03-data-merge: ok\n";
