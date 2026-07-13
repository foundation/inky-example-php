# Suite specification

This is the **language-neutral porting contract** for the Inky example suite.
It names, per example: what it teaches, which API surface it exercises, and
the REQUIRED OUTPUT MARKERS — grep-able strings in `dist/NN-name/` that
`composer run verify` (or the equivalent in each ported language) checks for.
Stage C ports (Node, Python, Ruby, Go) implement the same ten examples
against this spec and verify with the same markers, so a marker's exact
string is normative across languages; only the API-call syntax differs per
language.

Every example lives in `examples/NN-name/`, ships a `run.php` (the tutorial)
and a `verify.php` (the smoke test), and writes its output to
`dist/NN-name/`. `src/EmailRenderer.php` is the small production-shaped
renderer used by example 09 (example 10 mirrors its shell-caching idea by
hand, without calling it directly).

**Two coexisting fixture conventions, on purpose:**

- **Examples 01–08** reference shared fixtures (brand layout, includes,
  SCSS themes) in `shared/` at the repo root, via `../../shared/...`
  traversals from each example's `examples/NN-name/` base_path (see
  "Runtime requirements" §2 below for why it's double, not single, `..`).
  This is fine for tutorial fixtures: it keeps eight independent lessons
  from each carrying their own copy of the same handful of bytes, and
  every example that uses them is read in isolation, not as a model of a
  real app's directory layout.
- **Examples 09 and 10 (the capstones)** instead each ship a
  self-contained `emails/` tree — one `base_path` containing its own
  `layouts/`, `themes/`, and `includes/` copies, with every internal
  src/href written root-relative (no `../` anywhere inside the tree). This
  is deliberate: the capstones exist to model the shape a real CMS
  integration (Total CMS or similar) actually looks like on disk — one
  directory an app points its renderer at, self-contained, that a
  deployment can copy or version as a unit. A production integration
  doesn't reach two levels up into a repo-root `shared/` folder; it owns
  its own template tree. See each capstone's section below for the exact
  layout and the resolution rule that makes root-relative paths work
  regardless of which file inside the tree does the referencing.

Stage C ports should reproduce both conventions as described: 01–08 keep
the simple shared-fixture pattern (adjusted for that port's own
`examples/NN-name/` → `shared/` depth), while 09 and 10 each get their own
copied `emails/` base-root tree, not a reference back into a shared
directory.

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
| 09 | transactional (capstone) | real 3-email set on a self-contained `emails/` base-root tree via EmailRenderer | `EmailRenderer` | welcome/receipt/reset .html+.txt, receipt totals row, zero warnings |
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
(`EmailRenderer`) instead of raw `build` calls — one self-contained
base-root template directory (mirroring the tree a real CMS integration
would ship), per-email JSON data, and a build-shell cache.

**Structure — the self-contained `emails/` base-root tree** (see "The ten
examples" intro above for why this capstone differs from 01–08):

```
examples/09-transactional/emails/          <- the base_path (EmailRenderer templateDir)
├── welcome.inky, receipt.inky, password-reset.inky
├── layouts/main.html                      <- copy of shared/layout.html, adapted
├── themes/northwind.scss                  <- copy of shared/themes/northwind.scss
└── includes/header.html, footer.html      <- copies of shared/includes/*
```

`examples/09-transactional/data/*.json` and `cache/` (gitignored) sit
OUTSIDE `emails/`, as siblings — they're example-specific test fixtures
and build-shell cache, not part of the template tree a CMS would point a
renderer at, so `run.php` reads them directly rather than through
`EmailRenderer`.

**Inputs:** `emails/welcome.inky`, `emails/receipt.inky`,
`emails/password-reset.inky` (all using `emails/layouts/main.html`, no
template owns its own theme `<link>` — the renderer injects it), each
paired with its own `data/*.json` file. `receipt.inky` has a hand-written
`<table>` with a `{% for item in items %}` loop (wrapped in `<raw>`,
defense-in-depth exactly as in 03-data-merge — `data` merges in the same
call, so the loop is already expanded before the HTML parser runs) plus
three trailing rows computed from data: subtotal, shipping, and a
`<tr class="totals-row">` total.

**The resolution rule (KEY ENGINE RULE, stated in `emails/layouts/main.html`'s
own header comment where a reader will actually see it):** every relative
src/href anywhere under `emails/` resolves against `base_path` — the
ORIGINAL directory passed to `Inky::build()` (here, `emails/` itself, via
`EmailRenderer`'s `templateDir`) — regardless of which file inside the
tree contains the tag. `emails/layouts/main.html` lives one level below
the root but still writes `<include src="includes/header.html">`, not a
parent-directory traversal back up to it — a leading `../` there would
walk OUTSIDE `emails/` and fail, because `base_path` never shifts to
track the referencing file's own location. This is the same underlying
engine behavior documented in "Runtime requirements" §2 below for
`shared/` and 01–08 (`base_path` is always the original call-site value,
never re-derived per file); the capstones just apply it to a shallower,
self-contained tree instead of a shared one two levels up.

**API surface:** `EmailRenderer` (`src/EmailRenderer.php`). Constructed
with `templateDir` = `examples/09-transactional/emails` (which doubles as
the `build()` base_path — see "Runtime requirements" below and
`tests/email_renderer_test.php`'s own fixture layout, which follows the
same templateDir-doubles-as-base_path pattern, independent of this
capstone's directory shape), `themePath` = `emails/themes/northwind.scss`
(now INSIDE templateDir, not a shared path outside it — confirmed this
still produces the clean `themes/northwind.scss` href via
`relativeThemeHref()`'s `str_starts_with` branch, with no changes needed
to `EmailRenderer` itself), and a `cacheDir` under `cache/` (gitignored,
outside `emails/`). `->render($template, $data)` merges data, resolves
the theme, and caches the built shell. Template filenames passed to
`->render()` are now bare `"....inky"` names, relative to `emails/`
itself — `emails/` no longer nests a separate `templates/` subdirectory.

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

**Structure — the self-contained `emails/` base-root tree** (same
convention as 09-transactional — see "The ten examples" intro above for
why the capstones differ from 01–08):

```
examples/10-twig-cms/emails/               <- the base_path (passed to Inky::build())
├── newsletter.inky.twig
├── layouts/main.html                      <- copy of shared/layout.html, adapted
├── themes/northwind.scss                  <- copy of shared/themes/northwind.scss
└── includes/header.html, footer.html      <- copies of shared/includes/*
```

`run.php`'s Twig `FilesystemLoader` root and both `Inky::build()` calls
(Order A and Order B) all pass `emails/` as their base/base_path, so the
same self-contained tree serves both engines.

**Inputs:** `emails/newsletter.inky.twig` — a single file that is both a
valid inky template (`<layout src="layouts/main.html">`,
`<link href="themes/northwind.scss">`, `<container>`, `<raw>` — all
root-relative, per `emails/layouts/main.html`'s resolution-rule comment)
and a valid Twig template (`{{ subscriber.first_name }}`, `{{
subscriber.tier|loyalty_badge }}` — a custom, PHP-registered Twig filter
with no MiniJinja equivalent, proving genuine Twig extensibility beyond
what inky's own `data` merge speaks — and a `{% for product in products
%}` loop over 3 static products). The loop is wrapped in `<raw>`, and here
that's load-bearing, not defense-in-depth (see the file's own header
comment for the full explanation, written for CMS integrators). The
header comment also documents a narrower, empirically-found quirk (Twig
whitespace-control dashes needed on the loop tags) — load-bearing for
this example and instructive for anyone hitting the same shapes.

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

**10-twig-cms: engine-level findings.** Building this example
empirically surfaced real inky-core behaviors worth flagging beyond
the example itself:
1. `<raw>` protects its content from inky's component-transform HTML5
   parse, but NOT from the separate parse CSS inlining performs over that
   transform's output — a bare, still-unexpanded loop can be
   foster-parented out of its table at that second stage even though
   `<raw>` kept it safe at the first. Worked around here via
   `inline_css: false`.
2. Pipeline-level whitespace cleanup (`break_long_lines` /
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

## Porting notes for Stage C

A checklist for whoever ports this suite to Node, Python, Ruby, or Go.
Everything here is either a pointer back into this document or a fact that
doesn't fit neatly under one example.

1. **Two fixture conventions, applied to different examples — reproduce
   both, don't collapse them into one.**
   - **01–08** use the `../../shared/...` convention, and it's normative,
     not a PHP quirk: every example lives at `examples/NN-name/`, one
     level under `examples/`, itself one level under the repo root where
     `shared/` lives — so any reference to `shared/` from an example's own
     template, or from within `shared/layout.html`'s own includes, needs a
     double `../../`, not a single `../`. See "Runtime requirements" §2
     above for the empirical verification and the exact failure mode of
     getting it wrong. Reproduce the same `examples/NN-name/` → `shared/`
     depth in every port so the traversal count doesn't have to change per
     language.
   - **09 and 10 (the capstones) do NOT use `shared/` at all.** Each ships
     its own self-contained `emails/` base-root tree — `emails/layouts/`,
     `emails/themes/`, `emails/includes/`, each a COPY (not a symlink) of
     the corresponding `shared/` fixture, adapted so every internal
     src/href is root-relative (no `../` anywhere inside the tree; see the
     09 and 10 sections above for the exact layout, and
     `emails/layouts/main.html`'s own header comment for the resolution
     rule). This is intentional, not an oversight to reconcile with #1
     above: the capstones exist specifically to model what a real CMS
     integration's on-disk template directory looks like, and a real
     integration owns its own tree rather than reaching into a shared
     fixtures folder. Every Stage C port's 09 and 10 needs its own copied
     `emails/` tree, built the same way, not a reference back into that
     language's equivalent of `shared/`.

2. **Composer/runtime notes are PHP-specific mechanics, not requirements
   for other languages** — but each port needs its own equivalent sanity
   check up front (that language's own Task 1, mirroring this suite's):
   (a) does the local path-dependency mechanism (a path repository,
   `pip install -e`, a `Gemfile` path source, a Cargo path dependency,
   `npm link`) break the binding's dylib lookup the way a symlinked
   Composer path repo could have but didn't (see "Runtime requirements"
   §1)? (b) does the language's default dependency-stability policy need
   an equivalent to this repo's `minimum-stability: dev` /
   `prefer-stable: true` (a pure resolver artifact of depending on a
   `dev-*` local package — nothing to do with the dylib)? Document the
   answer the same way this file does, even if the answer is "no special
   wiring needed."

3. **Node has no pipeline binding.** The WASM/Node binding does not expose
   `build`, `validate`, or `migrate` (no filesystem access from WASM; a
   resolver-callback design is planned but not shipped). A straight port
   of examples 02–10 is therefore not possible in Node today — every one
   of them calls `build` (09 and 10 via `EmailRenderer`/direct calls), and
   06/07 call `validate`/`migrateWithDetails`. The Node port should:
   - Port 01-quickstart as-is — `transform` is the one pipeline-independent
     API surface and is fully supported over WASM.
   - For every other example, show the equivalent behavior through the
     `inky` CLI (`inky build`, `inky validate`, `inky migrate`) invoked as
     a subprocess from the example's entry point, rather than silently
     skipping the example or faking the API surface. Say so explicitly in
     each such example's own header comment — a reader should never
     wonder why a "Node example" shells out.
   - Do not weaken or reinterpret this suite's required output markers to
     make them CLI-shaped; the markers are about the resulting files in
     `dist/NN-name/`, and a CLI invocation produces the same files a
     library call would.

4. **`<raw>` is defense-in-depth in some examples, load-bearing in
   others — reproduce the distinction, not just the tag.** In 03 (and in
   09's receipt template) `data` is merged in the same call that builds
   the template, so MiniJinja expands `{% for %}` before the HTML5 parse
   ever runs; `<raw>` there is optional belt-and-suspenders, not required
   for correctness. In 10, the loop must SURVIVE the build unexpanded (the
   whole point of the cached-shell order), so at build time it's still
   literal `{% for %}` text sitting in a `<table>` — without `<raw>`,
   HTML5 foster-parenting silently relocates it out of the table and
   corrupts the output for every recipient. Ports must carry this
   distinction in both examples' teaching comments, not just copy the
   `<raw>` tag and call it done — a reader who only sees 03 could
   reasonably conclude `<raw>` around row loops is always cosmetic, which
   is false for 10's shape.

5. **Known live engine constraints to reproduce, not "fix" quietly** (see
   example 10 above for full detail): raw-preserved tags survive inky's
   own component-transform parse but NOT the separate parse the CSS
   inliner runs afterward over that transform's output — work around it
   with `inline_css: false` on any build whose output must still contain
   unexpanded template tags, exactly as example 10 does. Separately,
   inky-core's whitespace cleanup passes (`break_long_lines` /
   `collapse_closing_tags`) are not invariant to *when* a templating loop
   is expanded relative to inky's own build — only inter-tag whitespace is
   affected (rendering-insignificant, per inky-core's own comment), never
   content or structure, so any equality check across the two orders
   should normalize only that, the way 10's `run.php` does.
