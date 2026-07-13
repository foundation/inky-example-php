<?php

declare(strict_types=1);

/**
 * 08 — outlook-hybrid
 *
 * Building specifically for Outlook desktop: `hybrid: true` switches column
 * layout from nested tables to div-based columns wrapped in MSO ghost
 * tables (Outlook needs the table for layout; every other client gets
 * lighter div markup), and `bulletproof_buttons: true` renders every
 * <button> as VML (<v:roundrect>) inside an MSO conditional, falling back
 * to a normal table-based button everywhere else. launch.inky also uses
 * <outlook>/<not-outlook> directly for a banner that needs genuinely
 * different markup per client — see the comment in that file for how the
 * MSO conditional-comment pair works.
 */

require __DIR__ . '/../../bootstrap.php';

$dist = inky_example('08-outlook-hybrid');

$source = file_get_contents(__DIR__ . '/launch.inky');

$result = \Inky\Inky::build($source, __DIR__, [
    'hybrid' => true,
    'bulletproof_buttons' => true,
]);

file_put_contents($dist . '/launch.html', $result->html);

foreach ($result->warnings as $warning) {
    fwrite(STDERR, "warning: {$warning}\n");
}

// Both markers below prove Outlook-specific output actually landed: the
// bulletproof button's VML shape, and at least one MSO conditional comment
// (hybrid columns and the explicit <outlook>/<not-outlook> pair both emit
// these).
$msoCount = substr_count($result->html, '[if mso]') + substr_count($result->html, '[if !mso]');
$endifCount = substr_count($result->html, '[endif]');

echo "launch.html: " . strlen($result->html) . " bytes\n";
echo "MSO conditional opens: {$msoCount}, closes: {$endifCount}\n";
echo 'v:roundrect present: ' . (str_contains($result->html, 'v:roundrect') ? 'yes' : 'no') . "\n";
