<?php

declare(strict_types=1);

namespace Itineris\SagePay;

final class Terminator
{
    /**
     * Ends the request. Listeners on `gf_sagepay_terminate` run first and may only stop the exit by throwing.
     */
    public static function terminate(): never
    {
        do_action('gf_sagepay_terminate');
        exit;
    }
}
