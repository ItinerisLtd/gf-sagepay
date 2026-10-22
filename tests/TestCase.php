<?php

declare(strict_types=1);

namespace Itineris\SagePay\Tests;

use GFCache;
use GFFormDisplay;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testkit\TestCase as MantleTestCase;

use function class_exists;

/**
 * Wraps every test in a database transaction and clears Gravity Forms' static state.
 */
abstract class TestCase extends MantleTestCase
{
    use Refresh_Database;

    /**
     * True while a test runs. A bare `exit` mid-test would otherwise end Pest with status 0 and look like a pass.
     */
    public static bool $running = false;

    protected function setUp(): void
    {
        parent::setUp();

        GFCache::flush();
        self::$running = true;
    }

    protected function tearDown(): void
    {
        self::$running = false;

        if (class_exists(GFFormDisplay::class)) {
            GFFormDisplay::$submission = [];
        }

        parent::tearDown();
    }
}
