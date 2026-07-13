# Inky Example Suite: PHP

Ten runnable, numbered examples showing how to use the [Inky](https://github.com/foundation/inky)
email framework from PHP, via the `foundation/inky` binding — from the
smallest possible transform up to a transactional-email capstone and a
Twig/Total-CMS integration.

> This is the reference implementation for the suite; it is ported
> unchanged (same examples, same required output markers) to Node, Python,
> Ruby, and Rust. See [`SUITE.md`](SUITE.md) for the full, language-neutral
> spec.

> Requires Inky v2. See [installation instructions](https://github.com/foundation/inky).

## Prerequisites

- PHP >= 8.1 with the `ffi` extension enabled
- The `libinky` shared library (build from source in the `inky` repo:
  `cargo build -p inky-ffi --release`)
- Composer (the `foundation/inky` binding is pulled in via a local path
  repository pointing at `../inky/bindings/php`, so this repo must be
  checked out as a sibling of `inky/`)

## Quick Start

```bash
composer install
composer run examples   # runs every examples/*/run.php, writes dist/
composer run verify      # same, plus greps each output for required markers
```

## Layout

```
composer.json         path repo -> ../inky/bindings/php; twig/twig; scripts
bootstrap.php          autoload + inky_example() dist-dir helper, shared by every run.php
SUITE.md               language-neutral porting spec (the Stage C contract)
src/EmailRenderer.php  small production-shaped render/theme/cache wrapper (examples 09, 10)
shared/                brand layout, includes, SCSS themes used by every example
examples/NN-name/      one directory per example: run.php (tutorial) + verify.php (smoke test)
dist/                  build output (generated, gitignored)
build.php              runs every example (composer run examples / verify)
send.php               multipart send demo reading example 05's output
```

## Status

Examples are added incrementally; each lands with its own `run.php`,
`verify.php`, and a filled-in section of `SUITE.md`. Until then,
`composer run examples` succeeds with zero examples (there is nothing to
run yet).

## Documentation

- [Getting Started](https://github.com/foundation/inky/blob/develop/docs/getting-started.md)
- [Component Reference](https://github.com/foundation/inky/blob/develop/docs/components.md)
- [Language Bindings](https://github.com/foundation/inky/blob/develop/docs/bindings.md)
- [`SUITE.md`](SUITE.md) — this suite's own porting spec
