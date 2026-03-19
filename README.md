# Inky Example: PHP

A minimal example showing how to use the [Inky](https://github.com/foundation/inky) email framework from PHP via the FFI bindings.

> Requires Inky v2. See [installation instructions](https://github.com/foundation/inky).

## Prerequisites

- PHP >= 8.1 with FFI extension enabled
- The `libinky` shared library (build from source: `cargo build -p inky-ffi --release`)

## Quick Start

```bash
composer install
php build.php
```

## File Structure

```
src/emails/welcome.inky    Source template
data/welcome.json           Sample merge data
dist/                       Built output (generated)
build.php                   Build script
send.php                    Email sending example
```

## Building

`php build.php` transforms the Inky template, generates a merged version with sample data, and creates a plain text version.

## Sending

Edit `send.php` with your SMTP credentials, then:

```bash
php send.php
```

The example uses [PHPMailer](https://github.com/PHPMailer/PHPMailer). Install it with `composer require phpmailer/phpmailer`.

## Documentation

- [Getting Started](https://github.com/foundation/inky/blob/develop/docs/getting-started.md)
- [Component Reference](https://github.com/foundation/inky/blob/develop/docs/components.md)
- [Language Bindings](https://github.com/foundation/inky/blob/develop/docs/bindings.md)
