<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFAddOn;
use GFForms;

class GFSagePay
{
    public const VERSION = '0.10.10';

    public static function run(): void
    {
        // TODO: Check `\GFForms` is loaded.
        GFForms::include_payment_addon_framework();
        GFAddOn::register(AddOn::class);

        ConfirmationHandler::init();
    }
}
