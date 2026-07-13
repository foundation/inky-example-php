<?php

declare(strict_types=1);

/**
 * Plain-PHP smoke test for src/EmailRenderer.php — no test framework, just
 * assertions and an exit code. Run directly: `php tests/email_renderer_test.php`
 *
 * Exit 0 = all assertions passed. Exit 1 = at least one failed (message on STDERR).
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/EmailRenderer.php';

$failures = 0;

function check(string $label, bool $condition): void
{
    global $failures;
    if ($condition) {
        echo "  ok - {$label}\n";
    } else {
        echo "  FAIL - {$label}\n";
        $failures++;
    }
}

$templateDir = __DIR__ . '/fixtures/templates';
$themePath = __DIR__ . '/fixtures/theme.scss';
$cacheDir = sys_get_temp_dir() . '/inky-example-php-test-cache-' . getmypid();

// Clean slate for the cache directory used below.
if (is_dir($cacheDir)) {
    array_map('unlink', glob($cacheDir . '/*') ?: []);
    rmdir($cacheDir);
}

// --- 1. Basic render: data merge + theme color + plain text ---------------
echo "1. basic render (data merge, theme color, plain text)\n";
$renderer = new EmailRenderer($templateDir, $themePath);
$result = $renderer->render('sample.inky', ['name' => 'Ada'], ['inline_css' => true, 'framework_css' => true]);

check('html contains merged data value', str_contains($result->html, 'Ada'));
check('html contains compiled theme color (#123abc)', str_contains($result->html, '123abc'));
check('text is non-null', $result->text !== null);
check('no warnings for a clean template', $result->warnings === []);

// --- 2. Cache path: second render hits the cache ---------------------------
echo "2. cache path (second render with cacheDir hits cache)\n";
$cachingRenderer = new EmailRenderer($templateDir, $themePath, $cacheDir);
$first = $cachingRenderer->render('sample.inky', ['name' => 'Ada'], ['inline_css' => true, 'framework_css' => true]);

$cacheFiles = glob($cacheDir . '/*.json') ?: [];
check('exactly one cache file written after first render', count($cacheFiles) === 1);

$cacheFile = $cacheFiles[0] ?? null;
$mtimeBefore = $cacheFile !== null ? filemtime($cacheFile) : null;
// Back-date the cache file so a rewrite (i.e. a cache miss) would be detectable.
if ($cacheFile !== null) {
    touch($cacheFile, time() - 100);
    $mtimeBefore = filemtime($cacheFile);
}

$second = $cachingRenderer->render('sample.inky', ['name' => 'Ada'], ['inline_css' => true, 'framework_css' => true]);
$mtimeAfter = $cacheFile !== null ? filemtime($cacheFile) : null;

check('second render returns the same html as the first (from cache)', $second->html === $first->html);
check('cache file was not rewritten on the second render (cache hit)', $mtimeBefore === $mtimeAfter);
check('still exactly one cache file after second render', count(glob($cacheDir . '/*.json') ?: []) === 1);

// --- 3. Failure path: missing include throws BuildException ---------------
echo "3. failure path (missing include throws BuildException)\n";
$threw = false;
$warningsOnException = null;
try {
    $renderer->render('broken.inky');
} catch (\Inky\BuildException $e) {
    $threw = true;
    $warningsOnException = $e->warnings;
}
check('BuildException is thrown for a missing include', $threw);
check('BuildException exposes a warnings array', is_array($warningsOnException));

// --- cleanup -----------------------------------------------------------
if (is_dir($cacheDir)) {
    array_map('unlink', glob($cacheDir . '/*') ?: []);
    rmdir($cacheDir);
}

echo "\n";
if ($failures === 0) {
    echo "All assertions passed.\n";
    exit(0);
}

fwrite(STDERR, "{$failures} assertion(s) failed.\n");
exit(1);
