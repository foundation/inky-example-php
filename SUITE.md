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

*(filled in by the task that builds this example)*

### 02 — build-pipeline

*(filled in by the task that builds this example)*

### 03 — data-merge

*(filled in by the task that builds this example)*

### 04 — theming

*(filled in by the task that builds this example)*

### 05 — plain-text

*(filled in by the task that builds this example)*

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
