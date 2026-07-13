<?php

declare(strict_types=1);

/**
 * 03 — data-merge
 *
 * Merging JSON data into a template: plain variables ({{ customer.name }}),
 * a conditional ({% if gift %}), and a loop over line items. The loop
 * lives inside a real HTML <table> for the line items, so it's wrapped in
 * <raw> — see the comment in order.inky for why that's required there.
 */

require __DIR__ . '/../../bootstrap.php';

$dist = inky_example('03-data-merge');

$source = file_get_contents(__DIR__ . '/order.inky');
$data = json_decode(file_get_contents(__DIR__ . '/data.json'), true, flags: JSON_THROW_ON_ERROR);

// The 'data' option turns on MiniJinja merging; without it {{ }} / {% %}
// pass through untouched (useful when an ESP does its own merging).
$result = \Inky\Inky::build($source, __DIR__, ['data' => $data]);

file_put_contents($dist . '/order.html', $result->html);

foreach ($result->warnings as $warning) {
    fwrite(STDERR, "warning: {$warning}\n");
}

echo "order.html: " . strlen($result->html) . " bytes\n";
