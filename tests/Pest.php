<?php

declare(strict_types=1);

use Itineris\SagePay\Tests\TestCase;
use Mantle\Testing\Installation_Manager;

use function Mantle\Testing\manager;

require_once __DIR__ . '/TestCase.php';

uses(TestCase::class)->in('Integration');

// Resolved now, because Mantle changes directory before the after_rsync callback runs.
$gravityFormsPath = realpath(getenv('GRAVITYFORMS_PATH') ?: __DIR__ . '/gravityforms/vendor/gravity/gravityforms');

manager()
    ->maybe_rsync_plugin('gf-sagepay')
    ->exclusions(['release', 'tests/gravityforms/vendor'])
    ->after_rsync(static function (Installation_Manager $manager, string $installPath) use ($gravityFormsPath): void {
        if (false === $gravityFormsPath || ! is_file($gravityFormsPath . '/gravityforms.php')) {
            throw new RuntimeException(
                'Gravity Forms not found. Run `composer install --working-dir=tests/gravityforms` or set GRAVITYFORMS_PATH.',
            );
        }

        $target = $installPath . '/wp-content/plugins/gravityforms/';
        exec('rsync -aWq --delete ' . escapeshellarg($gravityFormsPath . '/') . ' ' . escapeshellarg($target), $output, $status);

        if (0 !== $status) {
            throw new RuntimeException('Copying Gravity Forms into the test install failed: ' . implode("\n", $output));
        }
    })
    ->with_plugins(['gravityforms/gravityforms.php', 'gf-sagepay/gf-sagepay.php'])
    ->loaded(static function (): void {
        require __DIR__ . '/bootstrap-hooks.php';
    })
    ->install();
