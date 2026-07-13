<?php

declare(strict_types=1);

/**
 * 07 — migrate
 *
 * Upgrading a v1 Inky template to v2 syntax: <columns large="6"> becomes
 * <column lg="6">, <h-line> becomes <divider>, <spacer size="..."> becomes
 * <spacer height="...">, class-based button/menu modifiers become
 * attributes, and <center><menu>...</menu></center> becomes
 * <menu align="center">. migrateWithDetails() returns both the rewritten
 * HTML and a human-readable list of what changed — useful as a one-time
 * upgrade script, or as a report to sanity-check before trusting the
 * output.
 */

require __DIR__ . '/../../bootstrap.php';

$dist = inky_example('07-migrate');

$legacy = file_get_contents(__DIR__ . '/legacy-v1.inky');

$result = \Inky\Inky::migrateWithDetails($legacy);

echo "Changes (" . count($result['changes']) . "):\n";
foreach ($result['changes'] as $change) {
    echo "  - {$change}\n";
}

// Write the migrated v2 template to disk — this is what you'd commit in
// place of the old file after reviewing the change list above.
file_put_contents($dist . '/migrated.inky', $result['html']);

// Prove the migrated template still builds cleanly end to end.
$built = \Inky\Inky::build($result['html'], __DIR__);
file_put_contents($dist . '/email.html', $built->html);

foreach ($built->warnings as $warning) {
    fwrite(STDERR, "warning: {$warning}\n");
}

echo "\nmigrated.inky: " . strlen($result['html']) . " bytes\n";
echo "email.html:    " . strlen($built->html) . " bytes\n";
