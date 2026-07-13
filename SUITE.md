# Suite specification

This is the **language-neutral porting contract** for the Inky example suite.
It names, per example: what it teaches, which API surface it exercises, and
the REQUIRED OUTPUT MARKERS — grep-able strings in `dist/NN-name/` that
`composer run verify` (or the equivalent in each ported language) checks for.
Stage C ports (Node, Python, Ruby, Rust) implement the same ten examples
against this spec and verify with the same markers, so a marker's exact
string is normative across languages; only the API-call syntax differs per
language.

Every example lives in `examples/NN-name/`, ships a `run.php` (the tutorial)
and a `verify.php` (the smoke test), and writes its output to
`dist/NN-name/`. Shared fixtures (brand layout, includes, SCSS themes) live
in `shared/` at the repo root. `src/EmailRenderer.php` is the small
production-shaped renderer used by examples 09 and 10.

## The ten examples

| # | Name | Teaches | API surface | Required output markers |
|---|------|---------|-------------|------------------------|
| 01 | quickstart | smallest possible transform | `Inky::transform` | `class="button"`, no `<button` |
| 02 | build-pipeline | layout+includes+SCSS via build | `Inky::build` + base_path | `<html`, header include text, compiled CSS present |
| 03 | data-merge | vars, conditionals, row loop in `<raw>` | `build` + `data` | order number, 3 `<tr>` line items, `{% for %}` absent |
| 04 | theming | same template, two themes | `build` twice, different SCSS links | theme A color in out A, theme B color in out B, colors differ |
| 05 | plain-text | multipart html+text | `build` + `plain_text` | `.txt` exists, shares headline with `.html` |
| 06 | validate-gate | CI gate before sending | `Inky::validate` | exits 1 on a bad template, prints rule ids; exits 0 on good |
| 07 | migrate | v1→v2 upgrade | `Inky::migrate(WithDetails)` | `lg="` present, `large="` absent, change list printed |
| 08 | outlook-hybrid | hybrid mode, bulletproof buttons, `<outlook>` | `build` + `hybrid` + `bulletproof_buttons` | `<!--[if mso]>`, `v:roundrect` |
| 09 | transactional (capstone) | real 3-email set on shared layout/theme via EmailRenderer | `EmailRenderer` | welcome/receipt/reset .html+.txt, receipt totals row, zero warnings |
| 10 | twig-cms | BOTH Twig orders + timing | Twig + `build`; `build` once + Twig per-recipient | both outputs byte-equal, timing lines, `<raw>` teaching comment |

Per-example detail sections (inputs, exact marker strings, notes) are filled
in by the task that builds each example.

### 01 — quickstart

**Teaches:** the smallest possible thing Inky does — turning responsive-grid
markup into email-safe table HTML, with no layout, theme, or data.

**Inputs:** one inline template (≤15 lines): a `<container>`/`<row>`/`<column>`
shipping notice with a single `<button>` component. No shared fixtures, no
data file.

**API surface:** `transform` only (the bare component-to-table conversion —
no layout resolution, no SCSS, no data merge).

**Output:** `output.html`. The run prints the template's and the output's
byte sizes, showing how much a single `<button>` expands into.

**Required output markers:** `class="button"` present (the button component
transformed); no literal `<button` tag remains.

### 02 — build-pipeline

**Teaches:** the full build pipeline in one call — a shared brand layout
(which itself pulls in the shared header/footer includes), a linked SCSS
theme, and CSS inlining.

**Inputs:** one template referencing the shared layout
(`<layout src="../../shared/layout.html" title="...">`) and linking the
shared northwind theme (`<link rel="stylesheet" href="../../shared/themes/northwind.scss">`,
placed immediately after the `<layout>` tag). The layout brings in both
shared includes (header + footer) automatically — the example does not
declare its own `<include>` tags.

**API surface:** `build` with `base_path` set to the example's own directory
(every relative path in the template, and in anything it includes, resolves
against that original base_path — see "Runtime requirements" below).

**Output:** `email.html`.

**Required output markers:** `<html` (the layout resolved); the shared
header/footer wordmark text "Northwind Coffee" (proves both includes
resolved — the template's own `<layout title="...">` overrides the
layout's default title, so this string can only come from the includes);
the compiled, unminified northwind theme color `#6f4e37` (proves the linked
SCSS was found, compiled, and inlined).

### 03 — data-merge

**Teaches:** merging JSON data into a template — plain variables, a
conditional, and a loop over line items rendered as real `<tr>` rows.

**Inputs:** an order-confirmation template (using the shared layout) with
`{{ customer.name }}`/`{{ order_number }}` variables, an
`{% if gift %}...{% endif %}` conditional wrapping a `<callout>`, and a
`{% for item in items %}` loop that emits one `<tr class="line-item">` per
item inside a hand-written HTML `<table>`. `data.json` supplies an order
with exactly 3 line items.

**API surface:** `build` with the `data` option (turns on MiniJinja merging;
without it, `{{ }}`/`{% %}` pass through untouched — useful when an ESP does
its own merging).

**The `<raw>` gotcha:**
When template tags are merged in the same build call (the `data` option),
loops expand into real rows before parsing — `<raw>` is optional
defense-in-depth. When tags must SURVIVE the build unexpanded (building a
reusable shell that a template engine renders later, as in example 10),
`<raw>` is REQUIRED around row-level tags: surviving bare `{% %}` text
directly inside `<table>`/`<tbody>` gets relocated by HTML5 parsing rules.
Ports must reproduce this distinction in their example 03 comment.

**Output:** `order.html`.

**Required output markers:** the order number `NW-10482`; exactly 3
`<tr class="line-item"` rows; no `{%` residue anywhere in the output.

### 04 — theming

**Teaches:** building the identical template twice with a different linked
SCSS theme each time.

**Inputs:** one promo template (`promo.inky`) whose theme `<link>` href
contains a placeholder (`__THEME__`, not real Inky syntax) instead of a
literal theme name.

**API surface:** `build` called twice against the same base template
source; between calls, the placeholder is substituted with `northwind` or
`midnight` before the source is handed to `build`. (The alternative —
shipping two `<link>` lines and stripping one per build — was considered
and rejected: the placeholder-substitution approach makes the one thing
that changes between builds explicit at the call site instead of implicit
inside the template.)

**Output:** `promo-northwind.html`, `promo-midnight.html`.

**Required output markers:** `#6f4e37` in `promo-northwind.html`; `#4a6cf7`
in `promo-midnight.html`; the two files are not byte-identical.

### 05 — plain-text

**Teaches:** deriving a plain-text alternative alongside the HTML, for
multipart transactional email.

**Inputs:** a weekly digest template (using the shared layout) with a
headline, several sections, and a CTA button.

**API surface:** `build` with `plain_text: true` — `BuildResult::$text`
carries the derived plain-text version (same content, tags and styling
stripped) alongside `$html`.

**Output:** `digest.html`, `digest.txt`.

**Required output markers:** `digest.txt` exists; it shares the digest
headline with `digest.html` (the plain-text renderer uppercases headings,
so the comparison is case-insensitive); it contains no `<` character
anywhere.

`send.php` (repo root) reads this example's output and shows, with the
real SMTP call commented out, how the html/text parts become a
`multipart/alternative` message (PHPMailer usage plus the raw MIME
structure spelled out by hand).

### 06 — validate-gate

**Teaches:** using `validate` as a pre-send CI gate — block a send when a
template has error-severity findings, but let warnings through.

**Inputs:** two standalone component templates (no shared layout — `validate`
operates on the source, transformed by the bare component pipeline, not the
full build): `good.inky` (a shipping-notice snippet with a preheader `<span>`
and a `<button href="...">`) and `bad.inky` (the same snippet with the
preheader removed and the button's `href` dropped — this trips two distinct
rules: `button-no-href`, an *error*, and `missing-preheader`, a *warning*).

**API surface:** `validate` — returns a list of `{severity, rule, message}`
diagnostics. A diagnostic is `error` severity (blocks the gate) or `warning`
severity (reported but non-blocking).

**The reusable gate shape:** a function `validate_or_fail(paths)` that
validates each path, prints its diagnostics grouped by severity, and exits
the process with a failure code the moment any path has an error-severity
diagnostic. This is meant to be copied directly into a CI step or a
pre-send hook — one call, no per-caller bookkeeping.

**Runner seam:** this is the one example where "run it" and "run it
successfully" are different things by design — the whole point is to show
a failing gate. Called with explicit path(s) as arguments, `run.php` is the
real gate: it exits 1 iff any given path has an error, exits 0 otherwise.
Called with no arguments (the shape `composer run examples` uses for every
example), it instead demonstrates BOTH outcomes — validating `good.inky`
and `bad.inky` in turn, printing each one's diagnostics and the exit code
the gate *would* have produced — without ever calling the real
failure exit itself, so the suite runner can execute every example
unconditionally. Ports should document this seam explicitly at the top of
their equivalent entry point, and their suite runner should special-case
this example's own exit code the same way (but must still run its smoke
test).

**Output:** none required for the demo path beyond stdout (a `report.txt`
transcript is written to `dist/06-validate-gate/` as a courtesy, not a
verified marker).

**Required output markers (verified via subprocess, not dist files):**
invoking the entry point with `bad.inky`'s path only exits 1, and its
combined stdout+stderr contains both rule ids `button-no-href` and
`missing-preheader`; invoking it with `good.inky`'s path only exits 0.

### 07 — migrate

**Teaches:** upgrading a v1 Inky template to v2 syntax programmatically,
with a change report you can review before trusting the rewrite.

**Inputs:** `legacy-v1.inky`, a two-column promo using v1 syntax throughout:
`<columns large="6" small="12">` (plural tag, `large`/`small` attributes),
`<h-line>`, `<spacer size="16">`, a `<button class="large expand">` (class-
based modifiers), and a `<center><menu>...</menu></center>` wrapper.

**API surface:** `migrateWithDetails` — returns the rewritten HTML plus an
ordered list of human-readable change descriptions (one entry per
migration rule that fired, not per match).

**Output:** `migrated.inky` (the rewritten v2 template) and `email.html`
(the migrated template run through the ordinary `build` pipeline, proving
it's not just textually different but still builds cleanly).

**Required output markers:** `migrated.inky` contains `lg="` and contains
no `large="`; the change list has at least 5 entries; `email.html` contains
`<table` (the migrated template still builds to table-based email markup).

### 08 — outlook-hybrid

**Teaches:** building specifically for Outlook desktop, which renders HTML
email with Microsoft Word's layout engine rather than a browser engine —
two build options plus one pair of components exist because of this:
`hybrid: true` switches column layout from nested tables to div-based
columns wrapped in MSO "ghost table" conditional comments (Outlook needs
the table for layout, every other client gets lighter div markup);
`bulletproof_buttons: true` renders every `<button>` as VML
(`<v:roundrect>`) inside an MSO conditional, falling back to an ordinary
table-based button everywhere else; and `<outlook>`/`<not-outlook>` let a
template branch on client directly when the two need genuinely different
markup, not just different CSS.

**Inputs:** `launch.inky`, a product-launch announcement (shared layout +
northwind theme) with an `<outlook>`/`<not-outlook>` pair around a banner
(Outlook gets a plain bordered `<table>`, everyone else gets a CSS
gradient `<div>` with rounded corners) and one ordinary `<button>`. A short
comment directly above the pair explains, in one paragraph, what an MSO
conditional comment is and why the split is needed — the one "gotcha"
worth teaching here.

**API surface:** `build` with `hybrid: true` and `bulletproof_buttons:
true`, plus the `<outlook>`/`<not-outlook>` components used directly in
the template.

**Output:** `launch.html`.

**Required output markers:** `<!--[if mso]>` present; `v:roundrect`
present; every MSO conditional open (`[if mso]` or `[if !mso]`, from
hybrid columns, bulletproof buttons, AND the explicit `<outlook>`/
`<not-outlook>` pair) has a matching `[endif]` close — counts must be
equal and non-zero.

### 09 — transactional (capstone)

**Teaches:** a realistic three-email transactional set for a single product
(Northwind Coffee) built through a small production-shaped service class
(`EmailRenderer`) instead of raw `build` calls — one shared theme, one
template directory, per-email JSON data, and a build-shell cache.

**Inputs:** `templates/welcome.inky`, `templates/receipt.inky`,
`templates/password-reset.inky` (all using the shared layout, no template
owns its own theme `<link>` — the renderer injects it), each paired with
its own `data/*.json` file. `receipt.inky` has a hand-written `<table>`
with a `{% for item in items %}` loop (wrapped in `<raw>`, defense-in-depth
exactly as in 03-data-merge — `data` merges in the same call, so the loop
is already expanded before the HTML parser runs) plus three trailing rows
computed from data: subtotal, shipping, and a `<tr class="totals-row">`
total.

**API surface:** `EmailRenderer` (`src/EmailRenderer.php`). Constructed
with `templateDir` = the example's OWN directory (`examples/09-transactional`,
not its `templates/` subdirectory — templateDir doubles as the `build()`
base_path, which must sit at the same `examples/NN-name/` depth as every
other example; template filenames passed to `->render()` are therefore
`"templates/....inky"`, relative to that base_path — see "Runtime
requirements" below and `tests/email_renderer_test.php`'s own fixture
layout, which follows the identical pattern), the shared northwind theme
path, and a `cacheDir` under `cache/` (gitignored). `->render($template,
$data)` merges data, resolves the theme, and caches the built shell.

**Output:** `welcome.html` + `.txt`, `receipt.html` + `.txt`,
`password-reset.html` + `.txt` (six files total; `plain_text` defaults to
true inside `EmailRenderer::render()`).

**Cache behavior:** `run.php` detects a cache hit/miss from the outside
(EmailRenderer doesn't report it directly) by counting `cache/*.json`
files before and after each render — unchanged means the render reused an
existing cache entry. It prints a `Cache` column per email. Running
`run.php` a second time shows all three emails as hits.

**Required output markers:** all six files exist; `receipt.html` contains
a `$`-amount inside `<tr class="totals-row">...</tr>` (proves the computed
total, not just a line item, rendered); a second `run.php` invocation's
output contains the string `hit (served from cache)` exactly 3 times;
zero warnings are reported across all three templates (the suite's own
templates must be warning-clean — a real warning means fixing the
template, not loosening this check).

### 10 — twig-cms

**Teaches:** integrating inky into a Twig-based CMS (the shape Total CMS
takes) — two valid orders for combining a real Twig render with an inky
`build`, and the one rule (`<raw>`) that makes the CMS's preferred fast
path (build once, render per recipient) safe.

**Inputs:** `newsletter.inky.twig` — a single file that is both a valid
inky template (`<layout>`, `<container>`, `<raw>`) and a valid Twig
template (`{{ subscriber.first_name }}`, `{{ subscriber.tier|loyalty_badge
}}` — a custom, PHP-registered Twig filter with no MiniJinja equivalent,
proving genuine Twig extensibility beyond what inky's own `data` merge
speaks — and a `{% for product in products %}` loop over 3 static
products). The loop is wrapped in `<raw>`, and here that's load-bearing,
not defense-in-depth (see the file's own header comment for the full
explanation, written for CMS integrators). The header comment also
documents two narrower, empirically-found quirks (Twig whitespace-control
dashes needed on the loop tags; a `"$<span>...</span>"` split to avoid a
real inky-core bug in layout/yield substitution when literal `"$" +
digits` reaches it) — both are load-bearing for this example and
instructive for anyone hitting the same shapes.

**API surface:**
- **Order A (Twig first):** a full Twig `Environment` renders the
  complete document per recipient (real data, real filters), THEN
  `Inky::build` (no `data` option) runs the ordinary build pipeline over
  that already-rendered HTML. Simple, always correct, one inky build per
  recipient.
- **Order B (inky first — the CMS fast path):** `Inky::build` runs ONCE,
  with no `data` option, on the raw `.inky.twig` source (Twig syntax
  passes through untouched, same no-op behavior as 03-data-merge without
  `data`) producing a "shell" that still contains literal Twig syntax;
  Twig then renders that cached shell per recipient. One inky build total,
  regardless of recipient count.
- Both builds use `inline_css: false` (see the code comment in `run.php`
  for why this is load-bearing, not cosmetic: CSS inlining runs a second,
  separate HTML parse over inky's own transform output, and at
  shell-build time the still-unexpanded loop is not protected against
  that second parse the way it's protected against the first).

**The correctness claim:** Order A and Order B must produce the same
document for recipient 1. They do, with one caveat found and reported
while building this example — see "10-twig-cms: an engine-level finding"
below. `run.php`'s equality check normalizes only the specific
insignificant whitespace this caveat introduces (documented inline, with
inky-core's own justification cited) before comparing; every other byte —
all content, all attributes, all structure, all row counts — is compared
as-is and any real divergence still fails the check.

**Output:** `order-a-1.html`..`order-a-3.html`, `order-b-1.html`..
`order-b-3.html` (six files — un-normalized, so the raw whitespace
difference described above can be inspected directly).

**Required output markers:** all six files exist; `order-a-1.html` and
`order-b-1.html` are equal once insignificant inter-tag whitespace is
normalized; stdout contains a line matching `orderA: X ms, orderB: Y ms
(shell built once)`; no output file contains a literal `{{` (nothing
un-rendered survives in the final documents).

**10-twig-cms: an engine-level finding.** Building this example
empirically surfaced three real inky-core behaviors worth flagging beyond
the example itself (full detail, repro steps, and code pointers in
task-4-report.md):
1. `<raw>` protects its content from inky's component-transform HTML5
   parse, but NOT from the separate parse CSS inlining performs over that
   transform's output — a bare, still-unexpanded loop can be
   foster-parented out of its table at that second stage even though
   `<raw>` kept it safe at the first. Worked around here via
   `inline_css: false`.
2. `process_layout`'s `<yield>` substitution (`crates/inky-core/src/include.rs`)
   splices child content into the layout via `Regex::replace()`, which
   interprets `$`-prefixed sequences in the REPLACEMENT text as
   capture-group backreferences. Any literal `"$"` immediately followed by
   digits or a word character in a layout-based template's rendered
   content — e.g. a plain "$17.00" — silently loses the `"$"` and the
   digits/word up to the next `"$"` or line boundary. This affects any
   layout-based template with a literal dollar amount already present in
   its content when `build()` runs (not just Twig-rendered content), and
   is unrelated to `data`/MiniJinja, which merges AFTER this step and is
   unaffected. Worked around here by splitting the static `"$"` from the
   digits with a tag boundary (`"$<span>17.00</span>"`).
3. Pipeline-level whitespace cleanup (`break_long_lines` /
   `collapse_closing_tags` in `crates/inky-core/src/pipeline.rs`) inserts
   or folds newlines around table-structural tags unconditionally, based
   on whatever document is in front of it when it runs. Because Order A
   and Order B run inky's build at different points relative to Twig's
   loop expansion, a raw-protected, multi-row loop can come out of the two
   orders with different (but rendering-insignificant, per inky-core's own
   comment on `break_long_lines`) whitespace between rows.

## Runtime requirements

Two environment questions were verified empirically in Task 1 (infrastructure).
Both are load-bearing for every example and every Stage C port should
re-verify the equivalent behavior in its own binding/runtime.

### 1. Does the composer path-repo symlink break the FFI dylib lookup?

**No — no special wiring was needed.** `composer.json` installs
`foundation/inky` as a **symlinked path repository**
(`vendor/foundation/inky -> ../../../inky/bindings/php`, per
`{"options": {"symlink": true}}`). `Inky\Driver\FfiDriver::findLibrary()`
locates `libinky.dylib` via a path relative to its own `__DIR__`
(`__DIR__ . '/../../../../target/release/{name}'`).

Empirically, PHP resolves `__DIR__`/`__FILE__` through symlinks to the
file's real, underlying location before that relative traversal runs
(confirmed via `ReflectionClass::getFileName()`, which reports the real
path `/Users/joeworkman/Developer/inky/bindings/php/src/Driver/FfiDriver.php`,
not the symlinked `vendor/...` path). So the existing relative lookup finds
`../inky/target/release/libinky.dylib` correctly with zero changes, and
`\Inky\Inky::version()` succeeds immediately after `composer install`
with no explicit driver construction. `bootstrap.php` documents this and
leaves a commented `Inky::setDriver(new FfiDriver(...))` snippet in case a
future driver or platform breaks that assumption.

One unrelated composer fix was required: the path-repo package resolves to
`dev-develop`, which fails composer's default `minimum-stability: stable`
check. `composer.json` sets `"minimum-stability": "dev"` and
`"prefer-stable": true` (the latter keeps `twig/twig` on a stable release).
This is a composer-resolution detail, not a dylib-lookup workaround.

### 2. Do relative traversals resolve from an `examples/NN-name/` base_path?

**Yes, with `../../`, not `../`.** The build pipeline (`inky-core`) resolves
every `<layout src="...">`, `<include src="...">`, and
`<link rel="stylesheet" href="*.scss">` path with a single
`base_path.join(path)` — and critically, **`base_path` is always the
original value passed to `Inky::build()`, never re-derived from the
directory of whatever file is currently being processed.** This means a
`<include>` tag *inside* `shared/layout.html` resolves against the calling
example's `base_path`, not against `shared/`'s own location.

Since every example lives at `examples/NN-name/` — one level under
`examples/`, which is itself one level under the repo root where `shared/`
lives — the correct traversal from an example's `base_path` is **`../../shared/...`**,
not `../shared/...`. Verified empirically:

- `<layout src="../shared/layout.html">` (single `..`) **fails**:
  `Failed to load layout '../shared/layout.html' (resolved to
  '.../examples/00-scratch/../shared/layout.html'): No such file or directory`
- `<layout src="../../shared/layout.html">` (double `..`) **succeeds**, and
  the layout's own `<include src="../../shared/includes/header.html">` /
  `<include src="../../shared/includes/footer.html">` (also double `..`,
  for the same reason) resolve correctly too — confirmed by asserting the
  header/footer partial text and the `$title$` substitution appear in the
  built output.

**Convention for all later tasks:** every reference to `shared/` — from an
example's own template, or from within `shared/layout.html` itself — uses
`../../shared/...` when the referencing template's `base_path` is
`examples/NN-name/`. `shared/layout.html`'s includes already follow this
rule (see the comment at the top of that file).

### SCSS color compilation

`shared/themes/northwind.scss` sets `$primary-color: #6f4e37;` and
`shared/themes/midnight.scss` sets `$primary-color: #4a6cf7;`. Verified
empirically: `grass` (the SCSS compiler) emits both colors **verbatim,
lowercase, unshortened** — `#6f4e37` and `#4a6cf7` — no minification to a
3-digit shorthand occurred for either value (neither hex has matching
digit pairs, which is the condition under which `grass` would shorten
them). Examples 04 and 09 should grep for these exact lowercase forms.
If a future theme value happens to have matching digit pairs (e.g.
`#aabbcc`), re-verify the compiled form before writing its marker.
