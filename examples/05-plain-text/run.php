<?php

declare(strict_types=1);

/**
 * 05 — plain-text
 *
 * Every transactional/marketing email should ship with a plain-text
 * alternative for clients that prefer it (and for spam filters that
 * penalize HTML-only mail). The 'plain_text' option asks Inky::build()
 * to derive one from the same source, alongside the HTML.
 */

require __DIR__ . '/../../bootstrap.php';

$dist = inky_example('05-plain-text');

$source = file_get_contents(__DIR__ . '/digest.inky');

$result = \Inky\Inky::build($source, __DIR__, ['plain_text' => true]);

file_put_contents($dist . '/digest.html', $result->html);
// With plain_text enabled, $result->text holds the derived plain-text
// version — the same content, tags and styling stripped, ready to be the
// text/plain part of a multipart email (see send.php in the repo root).
file_put_contents($dist . '/digest.txt', $result->text);

echo 'digest.html: ' . strlen($result->html) . " bytes\n";
echo 'digest.txt:  ' . strlen($result->text) . " bytes\n";
