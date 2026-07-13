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

// templateDir (== base_path passed to Inky::build) must sit exactly two
// directories below the repo root, the same depth as examples/NN-name/,
// because shared/layout.html's own includes (e.g.
// "../../shared/includes/header.html") — and sample.inky's own
// `<layout src="../../shared/layout.html">` — resolve against that
// original base_path, not against the file's own directory (see SUITE.md
// "Runtime requirements"). tests/fixtures/ is that depth (tests, fixtures);
// the actual template files live one level deeper, in fixtures/templates/,
// so every render() call below passes a "templates/..." relative filename.
$templateDir = __DIR__ . '/fixtures';
$themePath = __DIR__ . '/fixtures/theme.scss';
$cacheDir = sys_get_temp_dir() . '/inky-example-php-test-cache-' . getmypid();

// Clean slate for the cache directory used below.
if (is_dir($cacheDir)) {
    array_map('unlink', glob($cacheDir . '/*') ?: []);
    rmdir($cacheDir);
}

// --- 1. Basic render: layout-based template (the real architecture) -------
// This is the regression pin: sample.inky starts with `<layout src="...">`,
// exactly like every real template in this suite (see shared/layout.html
// and examples 09/10). It has NO literal `</head>` of its own — that tag
// only exists inside the layout file, which the child never sees directly.
// The old EmailRenderer::render() injected the theme <link> via a raw
// str_replace('</head>', ...) against the CHILD template source, before
// the layout was resolved — so on a layout-based template it silently
// matched nothing, and the theme was never compiled in. This assertion
// (the compiled color from the theme, #123abc) fails on that old code.
echo "1. basic render, layout-based template (data merge, theme color, plain text)\n";
$renderer = new EmailRenderer($templateDir, $themePath);
$result = $renderer->render('templates/sample.inky', ['name' => 'Ada'], ['inline_css' => true, 'framework_css' => true]);

check('html contains the layout document (<html)', str_contains($result->html, '<html'));
check('html contains merged data value', str_contains($result->html, 'Ada'));
check('html contains compiled theme color (#123abc)', str_contains($result->html, '123abc'));
check('no literal <link> tag survives (extractor stripped it)', !str_contains($result->html, '<link'));
check('text is non-null', $result->text !== null);
check('no warnings for a clean template', $result->warnings === []);

// --- 1b. Basic render: full-document template (no <layout>, has <head>) ---
// Keeps the OTHER injectThemeLink() branch covered: a template that is
// already a complete document with a literal </head> should still get the
// theme link spliced in before it (the pre-existing, still-correct path).
echo "1b. basic render, no-layout template (theme color still present)\n";
$noLayoutResult = $renderer->render('templates/no-layout.inky', ['name' => 'Ada'], ['inline_css' => true, 'framework_css' => true]);

check('no-layout html contains merged data value', str_contains($noLayoutResult->html, 'Ada'));
check('no-layout html contains compiled theme color (#123abc)', str_contains($noLayoutResult->html, '123abc'));
check('no-layout html has no literal <link> tag surviving', !str_contains($noLayoutResult->html, '<link'));

// --- 2. Cache path: second render hits the cache ---------------------------
echo "2. cache path (second render with cacheDir hits cache)\n";
$cachingRenderer = new EmailRenderer($templateDir, $themePath, $cacheDir);
$first = $cachingRenderer->render('templates/sample.inky', ['name' => 'Ada'], ['inline_css' => true, 'framework_css' => true]);

$cacheFiles = glob($cacheDir . '/*.json') ?: [];
check('exactly one cache file written after first render', count($cacheFiles) === 1);

$cacheFile = $cacheFiles[0] ?? null;
$mtimeBefore = $cacheFile !== null ? filemtime($cacheFile) : null;
// Back-date the cache file so a rewrite (i.e. a cache miss) would be detectable.
if ($cacheFile !== null) {
    touch($cacheFile, time() - 100);
    $mtimeBefore = filemtime($cacheFile);
}

$second = $cachingRenderer->render('templates/sample.inky', ['name' => 'Ada'], ['inline_css' => true, 'framework_css' => true]);
$mtimeAfter = $cacheFile !== null ? filemtime($cacheFile) : null;

check('second render returns the same html as the first (from cache)', $second->html === $first->html);
check('cache file was not rewritten on the second render (cache hit)', $mtimeBefore === $mtimeAfter);
check('still exactly one cache file after second render', count(glob($cacheDir . '/*.json') ?: []) === 1);

// --- 3. Failure path: missing include throws BuildException ---------------
echo "3. failure path (missing include throws BuildException)\n";
$threw = false;
$warningsOnException = null;
try {
    $renderer->render('templates/broken.inky');
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
