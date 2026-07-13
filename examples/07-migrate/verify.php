<?php
// Smoke test for 07-migrate. See SUITE.md "07 — migrate" for the required
// markers this checks.
require __DIR__ . '/../../bootstrap.php';

$dist = inky_example('07-migrate');
$migratedPath = $dist . '/migrated.inky';
$builtPath = $dist . '/email.html';

if (!is_file($migratedPath) || !is_file($builtPath)) {
    fwrite(STDERR, "07-migrate: missing dist output — run examples/07-migrate/run.php first\n");
    exit(1);
}

$migrated = file_get_contents($migratedPath);
$built = file_get_contents($builtPath);
$failures = 0;

if (!str_contains($migrated, 'lg="')) {
    fwrite(STDERR, "07-migrate: expected lg=\" in migrated output (large -> lg did not happen)\n");
    $failures++;
}

if (str_contains($migrated, 'large="')) {
    fwrite(STDERR, "07-migrate: found leftover large=\" in migrated output\n");
    $failures++;
}

// Re-run migrateWithDetails directly to check the reported change count
// (run.php only writes files; re-deriving here keeps this check honest
// without parsing run.php's stdout).
$legacy = file_get_contents(__DIR__ . '/legacy-v1.inky');
$result = \Inky\Inky::migrateWithDetails($legacy);
$changeCount = count($result['changes']);
if ($changeCount < 5) {
    fwrite(STDERR, "07-migrate: expected at least 5 changes, got {$changeCount}\n");
    $failures++;
}

if (!str_contains($built, '<table')) {
    fwrite(STDERR, "07-migrate: expected <table in built output (migrated template did not build to table markup)\n");
    $failures++;
}

if ($failures > 0) {
    exit(1);
}

echo "07-migrate: ok\n";
