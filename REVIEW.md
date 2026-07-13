# Review guide: Inky PHP example suite

This is the flagship example suite — the template Stage C will port
unchanged to Node, Python, Ruby, and Go. Everything is committed and
green at the current HEAD. This document is your entry point: how to run
it, a guided tour, and the three spots where I most want your taste before
this becomes the porting contract.

**This stage ends here — with your review, not a merge.** Nothing further
happens (no Stage C ports) until you've looked this over.

## How to run everything (3 commands)

```bash
cd ../inky && cargo build -p inky-ffi --release   # build libinky once (skip if already built)
cd ../inky-example-php
composer install
composer run verify   # runs all ten examples, writes dist/, checks every required marker
```

You should see `NN-name: ok` for all ten. Run a single example directly
with `php examples/03-data-merge/run.php` if you want to poke at one in
isolation; its output goes to `dist/03-data-merge/`.

## Guided tour

Read `run.php` in each directory top-to-bottom — every one is written as a
tutorial, not just a script. In order of increasing sophistication:

- **01-quickstart** — `Inky::transform` on a single `<button>`, no layout
  or data at all. Look at: how much table markup one `<button>` component
  expands into (`run.php` prints the byte counts).
- **02-build-pipeline** — the full `build` call: shared layout, includes,
  linked SCSS theme, CSS inlining, one call. Look at: `dist/02-build-pipeline/email.html`
  and see the whole document assembled from three separate source files.
- **03-data-merge** — JSON data merged into vars, a conditional, and a
  `{% for %}` loop over line items. Look at: the `<raw>` comment explaining
  it's optional here (defense-in-depth), setting up the contrast with 10.
- **04-theming** — the same template built twice with a placeholder-swapped
  theme link. Look at: the two output colors differ, proving the swap
  actually took effect and isn't a stale-cache illusion.
- **05-plain-text** — `plain_text: true` derives a `.txt` alternative
  alongside the `.html`. Look at: `send.php` at the repo root, which turns
  this example's output into a real `multipart/alternative` MIME message.
- **06-validate-gate** — `validate` as a CI gate; note the "runner seam"
  comment at the top explaining why this is the one example whose demo
  path never actually fails, even though the real gate does.
- **07-migrate** — v1→v2 template migration with a human-readable change
  list. Look at: the migrated template still builds cleanly (`<table` in
  the final output), so this isn't just a text rewrite.
- **08-outlook-hybrid** — hybrid columns, bulletproof VML buttons, explicit
  `<outlook>`/`<not-outlook>` branching. Look at: the MSO conditional
  comment explaining *why* Outlook needs this, right above the markup.
- **09-transactional (capstone)** — three real emails (welcome, receipt,
  password reset) built through `EmailRenderer` instead of raw `build`
  calls. Look at: `receipt.inky`'s totals row and `src/EmailRenderer.php`'s
  `injectThemeLink()`, the one genuinely tricky piece of glue code in the
  whole suite.
- **10-twig-cms** — the Total CMS integration question: Twig-first or
  inky-first? Both orders run, both are timed, and they're proven to agree.
  Look at: the header comment in `newsletter.inky.twig` — it's written
  directly for a CMS integrator, not for someone learning Inky.

## Three decisions that want your taste

**1. Brand and copy voice.** Every example uses a fictional brand,
"Northwind Coffee" (a roaster/subscription shop), with realistic
transactional copy instead of lorem ipsum — e.g. 09-transactional's
welcome email opens "Welcome, {{ customer.name }}!" / "Your {{ plan }}
subscription is confirmed — your first bag ships this week, roasted to
order" (01-quickstart's shipping notice is the one with "Your beans are
on the way" / "Track your Northwind Coffee subscription box"), and the
receipt reads "Thanks for your order, {{ customer.name }}! Here's what
shipped." This voice was chosen because
it gives natural excuses to write a welcome, a receipt, and a password
reset without straining. If you want a different brand, a different
register (more formal/less chatty), or a different vertical entirely, this
is the moment to say so — it propagates into all five ported languages.

**2. `EmailRenderer`'s API shape.** `src/EmailRenderer.php` is deliberately
small: constructed with a template directory, a theme path, and an
optional cache directory; one public method, `render($template, $data,
$options = [])`, returns an `\Inky\BuildResult`. It injects the theme
`<link>` for you (branching on whether the template is layout-based, a
full document, or a bare fragment — see its own doc comment) and,
optionally, caches the built shell keyed on template+theme+options. This
is the shape both the transactional capstone (09) and the Twig/CMS
integration (10, conceptually — 10 doesn't call it directly, but mirrors
its shell-caching idea) are built around. If a real CMS integration would
want more from this class — explicit cache invalidation, multiple themes
per instance, async warnings — better to raise it now, before Stage C
copies the pattern five times over.

**3. Example 10's order recommendation for Total CMS.** The suite
implements and times both valid orders: Twig-first (simple, always
correct, one inky build per recipient) and inky-first (build the shell
once, let Twig render it per recipient — the CMS fast path). In the
current run, inky-first is roughly 4-5x faster per batch (see
`dist/10-twig-cms`'s printed `orderA: X ms, orderB: Y ms` line, and
SUITE.md's "10-twig-cms: engine-level findings" subsection for the full
detail behind that number). The example documents
this as a genuine trade-off rather than picking a winner outright: the
fast path requires `<raw>` around any surviving row-level tags and
`inline_css: false` on the shell build (inlining happens once, after the
final per-recipient render, in a real CMS). If you'd rather the suite
state a firmer recommendation for Total CMS specifically — "always use
the fast path unless X" — rather than "here are both, pick based on your
constraints," that's a copy change worth making before this becomes the
five-language contract.

## Known limitations

- **Node/WASM has no pipeline binding.** `build`, `validate`, and
  `migrate` aren't exposed over WASM (no filesystem access from that
  target; a resolver-callback design is planned but unbuilt). The Node
  Stage C port can only implement 01-quickstart (`transform`) as a direct
  library-call port; everything else will shell out to the `inky` CLI
  instead of calling a library API, and its examples will say so plainly.
  This is documented in `SUITE.md`'s "Porting notes for Stage C" section.
- **`<raw>` doesn't protect against the CSS inliner's own parse.** It
  protects unexpanded template tags from inky's component-transform HTML5
  parse, but CSS inlining runs a second, separate parse over that
  transform's output, and a still-unexpanded loop isn't safe from *that*
  parse. The current workaround (`inline_css: false` on shell builds, used
  in example 10) is documented as a live engine constraint, not something
  papered over silently.
- **Inky's whitespace cleanup passes aren't invariant to processing
  order.** `break_long_lines`/`collapse_closing_tags` normalize whitespace
  around table-structural tags based on whatever document is in front of
  them at the moment they run — so the same logical content can come out
  of Twig-first vs. inky-first with different (but rendering-insignificant,
  per inky-core's own comment) inter-tag whitespace. Example 10's
  correctness check normalizes only that specific whitespace class before
  comparing; everything else is a byte-for-byte comparison.
- **Example 06 is intentionally the one example whose default invocation
  never fails**, even though its whole point is a failing gate — see its
  "runner seam" comment. Every Stage C port needs the same special-casing
  in its own suite runner.

## Next steps

Stage C — porting these same ten examples, verified against the same
markers in `SUITE.md`, to Node, Python, Ruby, and Go — is queued but not
started. It begins only after you've reviewed this suite and given the
go-ahead; nothing in Stage C depends on anything beyond what's in
`SUITE.md` today, so feedback on the three decisions above is the highest-
leverage thing to give before that work starts.
