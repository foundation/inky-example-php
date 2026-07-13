<?php
// Smoke test for 08-outlook-hybrid. See SUITE.md "08 — outlook-hybrid" for
// the required markers this checks.
require __DIR__ . '/../../bootstrap.php';

$dist = inky_example('08-outlook-hybrid');
$path = $dist . '/launch.html';

if (!is_file($path)) {
    fwrite(STDERR, "08-outlook-hybrid: missing {$path} — run examples/08-outlook-hybrid/run.php first\n");
    exit(1);
}

$html = file_get_contents($path);
$failures = 0;

if (!str_contains($html, '<!--[if mso]>')) {
    fwrite(STDERR, "08-outlook-hybrid: expected '<!--[if mso]>' in output\n");
    $failures++;
}

if (!str_contains($html, 'v:roundrect')) {
    fwrite(STDERR, "08-outlook-hybrid: expected 'v:roundrect' (bulletproof button VML) in output\n");
    $failures++;
}

// Every MSO conditional open ("[if mso]" or "[if !mso]") must have a
// matching "[endif]" close.
$opens = substr_count($html, '[if mso]') + substr_count($html, '[if !mso]');
$closes = substr_count($html, '[endif]');
if ($opens === 0) {
    fwrite(STDERR, "08-outlook-hybrid: found no MSO conditional opens at all\n");
    $failures++;
} elseif ($opens !== $closes) {
    fwrite(STDERR, "08-outlook-hybrid: unbalanced MSO conditionals — {$opens} opens vs {$closes} closes\n");
    $failures++;
}

if ($failures > 0) {
    exit(1);
}

echo "08-outlook-hybrid: ok\n";
