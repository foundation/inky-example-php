<?php

declare(strict_types=1);

/**
 * Shared bootstrap for every example's run.php.
 *
 * Loads the Composer autoloader (which pulls in the `foundation/inky`
 * binding via the path repository in composer.json) and provides
 * inky_example(), a tiny helper that gives each example a clean output
 * directory under dist/.
 *
 * Runtime note (verified in Task 1 — see SUITE.md "Runtime requirements"):
 * composer installs foundation/inky as a SYMLINKED path repo
 * (vendor/foundation/inky -> ../../../inky/bindings/php). PHP resolves
 * __DIR__ / __FILE__ through that symlink to the binding's real location
 * before FfiDriver computes its relative "../../../../target/release"
 * lookup, so the auto-detected FfiDriver finds libinky.dylib without any
 * extra wiring here. No explicit driver construction is needed. If that
 * ever changes (e.g. a future driver resolves paths differently), wire it
 * explicitly here with:
 *
 *   \Inky\Inky::setDriver(new \Inky\Driver\FfiDriver(
 *       __DIR__ . '/../inky/target/release/libinky.dylib',
 *       __DIR__ . '/../inky/bindings/php/stubs/inky.h',
 *   ));
 */

require __DIR__ . '/vendor/autoload.php';

/**
 * Return the dist output directory for an example, creating it if needed.
 *
 * Every run.php starts with:
 *   require __DIR__ . '/../../bootstrap.php';
 *   $dist = inky_example('01-quickstart');
 */
function inky_example(string $name): string
{
    $dir = __DIR__ . '/dist/' . $name;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}
