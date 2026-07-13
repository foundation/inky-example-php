# Inky Example Suite: PHP

Ten runnable, numbered examples showing how to use the [Inky](https://github.com/foundation/inky)
email framework from PHP, via the `foundation/inky` binding — from the
smallest possible transform up to a transactional-email capstone and a
Twig/Total-CMS integration.

This is the reference implementation for the suite: the same ten examples,
against the same required output markers, are ported unchanged to Node,
Python, Ruby, and Go in Stage C. See [`SUITE.md`](SUITE.md) for the full,
language-neutral porting spec.

> Requires Inky v2. See the main [inky](https://github.com/foundation/inky) repo.

## Requirements

- PHP >= 8.1 with the `ffi` extension enabled
- A Rust toolchain, to build the `libinky` shared library:
  ```bash
  cd ../inky && cargo build -p inky-ffi --release
  ```
  (this repo must be checked out as a sibling of `inky/` — Composer's path
  repository and the binding's dylib lookup both assume that layout)
- Composer, to pull in the `foundation/inky` binding via a local path
  repository pointing at `../inky/bindings/php`
- `twig/twig` (pulled in automatically by Composer) — used only by example 10

## 60-second quick start

```bash
cd ../inky && cargo build -p inky-ffi --release   # build libinky once
cd ../inky-example-php
composer install                                  # pulls in foundation/inky via the path repo
composer run examples                             # runs every examples/*/run.php, writes dist/
composer run verify                               # same, plus greps every output for its required markers
```

`composer run verify` prints `ok` for all ten examples. Output lands in `dist/NN-name/` (gitignored).

## The ten examples

| # | Name | Teaches |
|---|------|---------|
| [01-quickstart](examples/01-quickstart) | quickstart | The smallest possible thing Inky does: `transform` turns a `<button>` into table markup, no layout or data involved. |
| [02-build-pipeline](examples/02-build-pipeline) | build-pipeline | The full build pipeline in one call: shared layout + includes + a linked SCSS theme, all resolved and inlined. |
| [03-data-merge](examples/03-data-merge) | data-merge | Merging JSON data into a template: variables, a conditional, and a loop rendered as real `<tr>` rows. |
| [04-theming](examples/04-theming) | theming | Building the identical template twice with a different linked SCSS theme each time. |
| [05-plain-text](examples/05-plain-text) | plain-text | Deriving a plain-text alternative alongside the HTML for multipart transactional email. |
| [06-validate-gate](examples/06-validate-gate) | validate-gate | Using `validate` as a pre-send CI gate: block on errors, let warnings through. |
| [07-migrate](examples/07-migrate) | migrate | Upgrading a v1 Inky template to v2 syntax programmatically, with a reviewable change report. |
| [08-outlook-hybrid](examples/08-outlook-hybrid) | outlook-hybrid | Building for Outlook desktop: hybrid column layout, bulletproof VML buttons, `<outlook>`/`<not-outlook>` branching. |
| [09-transactional](examples/09-transactional) | transactional (capstone) | A real three-email transactional set (welcome, receipt, password reset) built through `EmailRenderer`, a small production-shaped service class. |
| [10-twig-cms](examples/10-twig-cms) | twig-cms | Integrating Inky into a Twig-based CMS: both valid processing orders, timed, plus the one `<raw>` rule that makes the fast path safe. |

Run any single example directly, e.g. `php examples/03-data-merge/run.php` —
every `run.php` is a self-contained, top-to-bottom tutorial with comments at
each decision point.

## For Total CMS / CMS integrators

If you're wiring Inky into a Twig-based CMS (Total CMS or similar), start
at [09-transactional](examples/09-transactional) to see the
production-shaped `EmailRenderer` wrapper (`src/EmailRenderer.php`), then
read [10-twig-cms](examples/10-twig-cms) for the CMS-specific question:
should Twig or Inky run first? Both orders are implemented, timed, and
proven to agree — see that example's `run.php` and
`newsletter.inky.twig` header comment for the full trade-off, including the
one `<raw>`-plus-`inline_css: false` rule that makes the faster,
build-once-per-template order safe.

## Layout

```
composer.json         path repo -> ../inky/bindings/php; twig/twig; scripts
bootstrap.php          autoload + inky_example() dist-dir helper, shared by every run.php
SUITE.md               language-neutral porting spec (the Stage C contract)
REVIEW.md              guide for reviewing this suite: how to run it, what to look at
src/EmailRenderer.php  small production-shaped render/theme/cache wrapper (example 09; the pattern example 10 adapts for a Twig CMS)
shared/                brand layout, includes, SCSS themes used by every example
examples/NN-name/      one directory per example: run.php (tutorial) + verify.php (smoke test)
dist/                  build output (generated, gitignored)
build.php              runs every example (composer run examples / verify)
send.php               multipart send demo reading example 05's output
```

## Documentation

- [`SUITE.md`](SUITE.md) — this suite's language-neutral porting spec (the Stage C contract)
- [`REVIEW.md`](REVIEW.md) — guide for reviewing this suite
- [Getting Started](https://github.com/foundation/inky/blob/develop/docs/getting-started.md)
- [Component Reference](https://github.com/foundation/inky/blob/develop/docs/components.md)
- [Language Bindings](https://github.com/foundation/inky/blob/develop/docs/bindings.md)
