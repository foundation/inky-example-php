<?php

declare(strict_types=1);

/**
 * 02 — build-pipeline
 *
 * The full pipeline in one call: a shared brand <layout> (which itself
 * pulls in the shared header and footer <include>s), a linked SCSS theme,
 * and CSS inlining — everything Inky::transform() alone doesn't do.
 */

require __DIR__ . '/../../bootstrap.php';

$dist = inky_example('02-build-pipeline');

$source = file_get_contents(__DIR__ . '/email.inky');

// base_path anchors every relative <layout>/<include>/<link> path in the
// template AND in anything it includes (see SUITE.md "Runtime
// requirements" — the layout's own includes resolve against THIS path,
// not against shared/'s location). Passing __DIR__ (examples/02-build-pipeline/)
// is why every shared/ reference in email.inky and shared/layout.html
// uses "../../shared/...".
$result = \Inky\Inky::build($source, __DIR__);

file_put_contents($dist . '/email.html', $result->html);

foreach ($result->warnings as $warning) {
    fwrite(STDERR, "warning: {$warning}\n");
}

echo "email.html: " . strlen($result->html) . " bytes\n";
