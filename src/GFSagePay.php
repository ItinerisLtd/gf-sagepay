<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFAddOn;
use GFForms;
use Itineris\SagePay\Preflight\ProductionMode;

class GFSagePay
{
    public const VERSION = '0.9.2';

    public function run(): void
    {
        // TODO: Check `\GFForms` is loaded.
        GFForms::include_payment_addon_framework();
        GFAddOn::register(AddOn::class);

        ConfirmationHandler::init();

        add_action('preflight_checker_collection_register', [ProductionMode::class, 'register']);
    }
}
