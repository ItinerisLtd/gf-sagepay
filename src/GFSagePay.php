<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFAddOn;
use GFForms;

/**
 * @internal
 */
final class GFSagePay
{
    public const VERSION = '1.0.0'; // x-release-please-version.

    public static function run(): void
    {
        // TODO: Check `\GFForms` is loaded.
        GFForms::include_payment_addon_framework();
        GFAddOn::register(AddOn::class);

        ConfirmationHandler::init();
    }
}
