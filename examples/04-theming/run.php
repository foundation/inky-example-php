<?php

declare(strict_types=1);

/**
 * 04 — theming
 *
 * The exact same template, built twice with a different linked SCSS
 * theme each time. Two approaches would work here (substitute the href
 * in the template source, or ship two <link> lines and strip one); this
 * example substitutes a placeholder, because it makes the one line that
 * changes between builds explicit at the call site rather than buried in
 * the template.
 */

require __DIR__ . '/../../bootstrap.php';

$dist = inky_example('04-theming');

// promo.inky links "../../shared/themes/__THEME__.scss" — a placeholder,
// not real Inky syntax. Swapping it before each build is what makes the
// two outputs below differ only in theme.
$template = file_get_contents(__DIR__ . '/promo.inky');

foreach (['northwind', 'midnight'] as $theme) {
    $source = str_replace('__THEME__', $theme, $template);
    $result = \Inky\Inky::build($source, __DIR__);
    $outFile = $dist . "/promo-{$theme}.html";
    file_put_contents($outFile, $result->html);
    echo basename($outFile) . ': ' . strlen($result->html) . " bytes\n";
}
