<?php

declare(strict_types=1);

/**
 * 09 — transactional (capstone)
 *
 * A realistic three-email transactional set for Northwind Coffee — welcome,
 * receipt, password reset — built with EmailRenderer (src/EmailRenderer.php)
 * instead of raw Inky::build() calls. This is the shape a real app uses:
 * one shared theme, one template directory, JSON data per email, and a
 * build-shell cache so re-rendering the same email (e.g. a retried send)
 * skips redoing the work.
 *
 * Run this file twice in a row to see the cache take effect on the second
 * pass — composer run examples/verify already does, via build.php and
 * verify.php respectively.
 */

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/../../src/EmailRenderer.php';

$dist = inky_example('09-transactional');
$cacheDir = __DIR__ . '/cache';

// This emails/ dir is the convention: what you'd point Total CMS (or any
// real CMS) at — one self-contained base_path, everything under it
// root-relative (layouts/, themes/, includes/ — see emails/layouts/main.html
// for the resolution rule), no traversal outside the tree at all. It's the
// same base_path EmailRenderer's templateDir doubles as for Inky::build();
// template filenames passed to ->render() below are therefore bare
// "....inky" names, relative to emails/ itself, not a "templates/" prefix.
$renderer = new EmailRenderer(
    __DIR__ . '/emails',
    __DIR__ . '/emails/themes/northwind.scss',
    $cacheDir,
);

$emails = [
    ['name' => 'welcome', 'template' => 'welcome.inky', 'data' => 'data/welcome.json'],
    ['name' => 'receipt', 'template' => 'receipt.inky', 'data' => 'data/receipt.json'],
    ['name' => 'password-reset', 'template' => 'password-reset.inky', 'data' => 'data/password-reset.json'],
];

$summary = [];
$totalWarnings = 0;

foreach ($emails as $email) {
    $data = json_decode(
        file_get_contents(__DIR__ . '/' . $email['data']),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    // EmailRenderer doesn't report cache hit/miss directly, so detect it
    // from the outside: a cache HIT reads an existing file and writes
    // nothing new, so the cache directory's file count won't change.
    $before = count(glob($cacheDir . '/*.json') ?: []);
    $result = $renderer->render($email['template'], $data);
    $after = count(glob($cacheDir . '/*.json') ?: []);
    $cacheStatus = $after > $before ? 'miss (built + cached)' : 'hit (served from cache)';

    file_put_contents($dist . '/' . $email['name'] . '.html', $result->html);
    file_put_contents($dist . '/' . $email['name'] . '.txt', $result->text);

    $warningCount = count($result->warnings);
    $totalWarnings += $warningCount;

    $summary[] = [
        $email['name'],
        strlen($result->html) + strlen($result->text),
        $warningCount,
        $cacheStatus,
    ];
}

echo str_pad('Email', 18) . str_pad('Bytes', 10) . str_pad('Warnings', 10) . "Cache\n";
foreach ($summary as [$name, $bytes, $warnings, $cacheStatus]) {
    echo str_pad($name, 18) . str_pad((string) $bytes, 10) . str_pad((string) $warnings, 10) . "{$cacheStatus}\n";
}

echo "\ntotal warnings: {$totalWarnings}\n";
