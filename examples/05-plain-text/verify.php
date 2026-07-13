<?php
// Smoke test for 05-plain-text. See SUITE.md "05 — plain-text" for the
// required markers this checks.
require __DIR__ . '/../../bootstrap.php';

$dist = inky_example('05-plain-text');
$htmlPath = $dist . '/digest.html';
$txtPath = $dist . '/digest.txt';

if (!is_file($htmlPath) || !is_file($txtPath)) {
    fwrite(STDERR, "05-plain-text: missing digest.html and/or digest.txt — run examples/05-plain-text/run.php first\n");
    exit(1);
}

$html = file_get_contents($htmlPath);
$text = file_get_contents($txtPath);
$failures = 0;

// The plain-text renderer uppercases headings, so compare case-insensitively.
$headline = 'This week at Northwind Coffee';
if (!str_contains($html, $headline)) {
    fwrite(STDERR, "05-plain-text: expected the digest headline \"{$headline}\" in digest.html\n");
    $failures++;
}
if (stripos($text, $headline) === false) {
    fwrite(STDERR, "05-plain-text: expected the digest headline \"{$headline}\" in digest.txt\n");
    $failures++;
}

if (str_contains($text, '<')) {
    fwrite(STDERR, "05-plain-text: found a '<' character in digest.txt — plain text should have no markup\n");
    $failures++;
}

if ($failures > 0) {
    exit(1);
}

echo "05-plain-text: ok\n";
