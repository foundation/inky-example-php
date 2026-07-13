<?php

declare(strict_types=1);

/**
 * 06 — validate-gate
 *
 * A CI-style pre-send gate: run Inky::validate() over a template and block
 * a send if it has any *error*-severity diagnostic (warnings are reported
 * but don't block). validate_or_fail() below is the reusable gate — copy
 * it straight into a CI step or a pre-send hook.
 *
 * RUNNER SEAM — read this before changing how this file is invoked:
 *   - `php run.php <path> [<path> ...]` is the REAL gate. It calls
 *     validate_or_fail() on the given paths, which exits the process with
 *     1 if any of them has an error, or lets it fall through to exit 0
 *     otherwise. verify.php always invokes this file with explicit argv
 *     paths so it observes true exit codes (bad.inky alone -> exit 1,
 *     good.inky alone -> exit 0).
 *   - `php run.php` with NO args is the suite-runner path (this is what
 *     `composer run examples` / build.php calls for every example). This
 *     example's whole point is to demonstrate a FAILING gate, but
 *     build.php runs every example unconditionally, so the no-args branch
 *     demonstrates BOTH good.inky (passes) and bad.inky (fails) by hand —
 *     printing diagnostics and the exit code the gate WOULD have produced
 *     for each — without ever calling exit(1) itself. It always exits 0.
 *     (build.php also carries a matching exemption comment for this
 *     example, belt-and-suspenders.)
 */

require __DIR__ . '/../../bootstrap.php';

$dist = inky_example('06-validate-gate');

/**
 * Validate one template and print its diagnostics grouped by severity.
 * Returns true iff it has at least one error-severity diagnostic.
 */
function report_diagnostics(string $path): bool
{
    $html = file_get_contents($path);
    $diagnostics = \Inky\Inky::validate($html);

    $errors = array_values(array_filter($diagnostics, fn (array $d): bool => $d['severity'] === 'error'));
    $warnings = array_values(array_filter($diagnostics, fn (array $d): bool => $d['severity'] === 'warning'));

    echo basename($path) . ":\n";
    foreach ($errors as $d) {
        echo "  ERROR   [{$d['rule']}] {$d['message']}\n";
    }
    foreach ($warnings as $d) {
        echo "  WARNING [{$d['rule']}] {$d['message']}\n";
    }
    if ($diagnostics === []) {
        echo "  (clean)\n";
    }

    return $errors !== [];
}

/**
 * The reusable pre-send gate. Validate every path, print diagnostics for
 * each, and exit(1) as soon as it's known at least one has an error.
 */
function validate_or_fail(array $paths): void
{
    $hasErrors = false;
    foreach ($paths as $path) {
        if (report_diagnostics($path)) {
            $hasErrors = true;
        }
    }
    if ($hasErrors) {
        exit(1);
    }
}

$argPaths = array_slice($argv, 1);

if ($argPaths !== []) {
    // Real invocation: exactly what a CI step or pre-send hook would run.
    validate_or_fail($argPaths);
    echo "gate: passed\n";
    file_put_contents($dist . '/report.txt', "gate: passed for " . implode(', ', array_map('basename', $argPaths)) . "\n");
    exit(0);
}

// No-args demo path — see the header comment above.
$good = __DIR__ . '/good.inky';
$bad = __DIR__ . '/bad.inky';

$goodFailed = report_diagnostics($good);
echo '  -> gate would exit ' . ($goodFailed ? '1 (blocked)' : '0 (passed)') . "\n\n";

$badFailed = report_diagnostics($bad);
echo '  -> gate would exit ' . ($badFailed ? '1 (blocked)' : '0 (passed)') . "\n";

file_put_contents(
    $dist . '/report.txt',
    "good.inky would exit " . ($goodFailed ? 1 : 0) . "\n"
    . "bad.inky would exit " . ($badFailed ? 1 : 0) . "\n"
);
