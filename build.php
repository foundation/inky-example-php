<?php
// Runs every example in examples/*/run.php, in order.
// --verify additionally checks each example's required output markers.
require __DIR__ . '/bootstrap.php';

$verify = in_array('--verify', $argv, true);
$failures = 0;

foreach (glob(__DIR__ . '/examples/*/run.php') as $script) {
    $name = basename(dirname($script));
    echo "\n=== {$name} ===\n";
    passthru(PHP_BINARY . ' ' . escapeshellarg($script), $exit);
    if ($exit !== 0 && $name !== '06-validate-gate') { // 06 demonstrates a failing gate on purpose; its run.php manages its own exit codes
        fwrite(STDERR, "FAILED: {$name} (exit {$exit})\n");
        $failures++;
        continue;
    }
    if ($verify) {
        $check = dirname($script) . '/verify.php';
        if (file_exists($check)) {
            passthru(PHP_BINARY . ' ' . escapeshellarg($check), $vexit);
            if ($vexit !== 0) { fwrite(STDERR, "VERIFY FAILED: {$name}\n"); $failures++; }
        }
    }
}

exit($failures === 0 ? 0 : 1);
