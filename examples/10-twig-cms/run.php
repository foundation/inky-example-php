<?php

declare(strict_types=1);

/**
 * 10 — twig-cms
 *
 * See the header comment in newsletter.inky.twig for the full CMS-
 * integrator explanation of Order A vs. Order B and why <raw> is
 * load-bearing here (not just defense-in-depth, as in 03-data-merge), plus
 * three narrower quirks found empirically while building this example.
 * This file builds both orders against the same 3 recipients, asserts
 * recipient 1 comes out the same document either way (see the comment
 * above that check for exactly what "the same" means and why), and times
 * both paths.
 */

require __DIR__ . '/../../bootstrap.php';

$dist = inky_example('10-twig-cms');

// This is trusted, already-authored template content, not user input, so
// autoescape is off — matching how inky's own `data` merge behaves (no
// HTML escaping). It also keeps the two orders comparable: the SAME Twig
// environment renders in both orders, so whatever escaping policy is
// chosen applies identically either way; only the ORDER of Twig vs. inky
// differs between them.
$twig = new \Twig\Environment(
    new \Twig\Loader\FilesystemLoader(__DIR__),
    ['autoescape' => false],
);

// Genuinely Twig-only: inky's own `data` merge (MiniJinja) has no
// mechanism for user-registered filters from PHP. `|upper` in the
// template proves ordinary Twig syntax works; this filter proves real
// Twig extensibility that `data` alone cannot reach.
$twig->addFilter(new \Twig\TwigFilter('loyalty_badge', function (string $tier): string {
    return match ($tier) {
        'gold' => 'Gold roaster',
        'silver' => 'Silver roaster',
        default => 'Roaster',
    };
}));

// The template supplies the "$" as static text before the price variable.
$products = [
    ['name' => 'Colombia Huila, 12oz', 'price' => '17.00'],
    ['name' => 'Guatemala Antigua, 12oz', 'price' => '18.50'],
    ['name' => 'Decaf House Blend, 12oz', 'price' => '15.00'],
];

$recipients = [
    ['first_name' => 'Marcus', 'tier' => 'gold'],
    ['first_name' => 'Priya', 'tier' => 'silver'],
    ['first_name' => 'Devon', 'tier' => 'bronze'],
];

function newsletter_context(array $recipient, array $products): array
{
    return [
        'subscriber' => $recipient,
        'products' => $products,
        'shop_url' => 'https://northwindcoffee.example/shop',
    ];
}

$rawSource = file_get_contents(__DIR__ . '/newsletter.inky.twig');

// `inline_css: false` in BOTH builds below is load-bearing, not cosmetic —
// see the comment above Order B's build call for why.
$buildOptions = ['inline_css' => false];

// --- Order A: Twig first, then a full inky build, once PER RECIPIENT ------
$startA = hrtime(true);
$orderAOutputs = [];
foreach ($recipients as $recipient) {
    $twigHtml = $twig->render('newsletter.inky.twig', newsletter_context($recipient, $products));
    $orderAOutputs[] = \Inky\Inky::build($twigHtml, __DIR__, $buildOptions)->html;
}
$durationA = (hrtime(true) - $startA) / 1_000_000;

// --- Order B: inky ONCE (the shell), then Twig per recipient --------------
$startB = hrtime(true);
// No `data` option: Twig's {{ }} and {% %} pass through untouched (same
// no-op behavior as 03-data-merge without `data`). The <raw>-wrapped loop
// is the part that would otherwise be corrupted by HTML5 table
// foster-parenting — see the header comment in newsletter.inky.twig.
//
// `inline_css: false` here (and, to match, in Order A above too) works
// around a real inky-core limitation found while building this example:
// <raw> only protects its content from the FIRST HTML5 parse (component
// transform). CSS inlining runs a SEPARATE parse over that transform's
// output, and at shell-build time the reinjected loop is still literal
// {% for %}/{% endfor %} text sitting beside a <tr> inside <tbody> — which
// that second parse foster-parents out of the table, same failure mode as
// skipping <raw> entirely, just one stage later. Turning off per-tag
// inlining (framework_css stays on, so the compiled theme still ships as a
// <style> block) sidesteps the second parse and keeps both orders
// byte-comparable. inline_css: true remains fine for templates whose
// data is always fully merged before inky ever runs (09-transactional);
// it's specifically the survives-the-build, fill-in-later shape here that
// needs this.
$shell = \Inky\Inky::build($rawSource, __DIR__, $buildOptions)->html;
$shellTemplate = $twig->createTemplate($shell);
$orderBOutputs = [];
foreach ($recipients as $recipient) {
    $orderBOutputs[] = $shellTemplate->render(newsletter_context($recipient, $products));
}
$durationB = (hrtime(true) - $startB) / 1_000_000;

foreach ($recipients as $i => $recipient) {
    $n = $i + 1;
    file_put_contents("{$dist}/order-a-{$n}.html", $orderAOutputs[$i]);
    file_put_contents("{$dist}/order-b-{$n}.html", $orderBOutputs[$i]);
}

// The correctness claim: recipient 1 must be the same document either way
// inky and Twig are ordered. Investigated a real, reproducible divergence
// here while building this example: inky's pipeline-level cleanup passes
// (break_long_lines / collapse_closing_tags in inky-core's pipeline.rs)
// insert or fold newlines around every <table>/<tbody>/<tr>/<td>/<th> tag,
// unconditionally, on whatever document is in front of them at the moment
// they run. In Order A that's the FULLY-EXPANDED 3-row document (Twig ran
// first), so all 3 rows get normalized together in one pass. In Order B
// it's the ONE-ROW shell (inky ran first, before Twig had anything to
// expand) — that single row gets normalized once, and then Twig's blind
// per-recipient text repetition duplicates it verbatim, with no further
// inky pass afterward to reconcile the seams between copies. The row
// boundaries can end up whitespace-differently-normalized between the two
// orders as a result — a genuine engine-level finding (see SUITE.md's
// "10-twig-cms: engine-level findings" subsection), not something papered
// over here.
//
// It's ALSO exactly the whitespace inky-core's own break_long_lines
// comment calls out as safe to disturb: "Whitespace between table elements
// ... is ignored by email clients." So the comparison below normalizes
// only that — collapsing runs of whitespace strictly BETWEEN a closing
// '>' and the next '<' — before comparing. Any real content or structural
// difference (attributes, text, tag order, row count) still fails this
// check; only inter-tag padding is treated as insignificant, on inky's
// own authority. dist/ still holds the RAW, un-normalized output from both
// orders so the actual whitespace diff can be inspected directly.
function collapse_insignificant_table_whitespace(string $html): string
{
    return preg_replace('/>\s+</', '><', $html);
}

$normalizedA = collapse_insignificant_table_whitespace($orderAOutputs[0]);
$normalizedB = collapse_insignificant_table_whitespace($orderBOutputs[0]);
$identical = $normalizedA === $normalizedB;
echo 'recipient 1 identical between orders (ignoring inter-tag whitespace): '
    . ($identical ? 'yes' : 'NO — DIVERGENCE') . "\n";
if (!$identical) {
    fwrite(STDERR, "10-twig-cms: order-a-1.html and order-b-1.html diverged — see dist/10-twig-cms/ for a diff\n");
    exit(1);
}

printf("orderA: %.2f ms, orderB: %.2f ms (shell built once)\n", $durationA, $durationB);
