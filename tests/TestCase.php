<?php

declare(strict_types=1);

namespace Itineris\SagePay\Tests;

use GFCache;
use GFFormDisplay;
use GFPaymentAddOn;
use Itineris\SagePay\AddOn;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testkit\TestCase as MantleTestCase;
use ReflectionProperty;

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

        try {
            FakeSagePayHttpStrategy::reset();
            self::resetAddOnSubmissionState();

            if (class_exists(GFFormDisplay::class)) {
                GFFormDisplay::$submission = [];
            }
        } finally {
            parent::tearDown();
        }
    }

    /**
     * Gravity Forms keeps each submission's payment state and feed on the add-on singleton. Left in place, its
     * `gform_confirmation` filter turns every later confirmation into a redirect to the last checkout URL.
     */
    private static function resetAddOnSubmissionState(): void
    {
        if (! class_exists(GFPaymentAddOn::class)) {
            return;
        }

        $addOn = (new ReflectionProperty(AddOn::class, '_instance'))->getValue();
        if (! $addOn instanceof AddOn) {
            return;
        }

        $defaults = [
            'redirect_url' => '',
            'is_payment_gateway' => false,
            'authorization' => [],
            'current_submission_data' => false,
            'current_feed' => false,
            '_single_submission_feed' => false,
        ];

        // ReflectionProperty throws if Gravity Forms renames one, rather than silently adding a dynamic property.
        foreach ($defaults as $property => $default) {
            (new ReflectionProperty($addOn, $property))->setValue($addOn, $default);
        }
    }
}
