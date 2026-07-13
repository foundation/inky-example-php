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

**The `<raw>` gotcha:** the loop's `{% for %}`/`{% endfor %}` markers are
wrapped in `<raw>` because, sitting as bare text directly under `<table>`
(outside a `<tr>`/`<td>`), they are exactly the shape of content that
strict HTML5 tree-building treats as invalid and relocates out of the
table — `<raw>` keeps that literal block untouched through that step. The
template carries a 3-line comment explaining this at the loop site.

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

*(filled in by the task that builds this example)*

### 07 — migrate

*(filled in by the task that builds this example)*

### 08 — outlook-hybrid

*(filled in by the task that builds this example)*

### 09 — transactional (capstone)

*(filled in by the task that builds this example)*

### 10 — twig-cms

*(filled in by the task that builds this example)*

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
