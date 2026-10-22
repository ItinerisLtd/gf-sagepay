<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use function fastcgi_finish_request;
use function function_exists;
use function ignore_user_abort;
use function litespeed_finish_request;

final class Terminator
{
    /**
     * Ends the request.
     * Listeners on `gf_sagepay_terminate` run first and may only stop the exit by throwing.
     */
    public static function terminate(): never
    {
        do_action('gf_sagepay_terminate');
        self::finishResponse();
        exit;
    }

    /**
     * Closes the connection where the server supports it, so work PHP runs on shutdown
     * (such as Gravity Forms' queued notifications) does not delay the reply to SagePay.
     * It uses the same guard as WordPress core's wp-cron.php, including ignore_user_abort()
     * so shutdown work still completes once the client is gone.
     */
    private static function finishResponse(): void
    {
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            fastcgi_finish_request();

            return;
        }

        if (function_exists('litespeed_finish_request')) {
            ignore_user_abort(true);
            litespeed_finish_request();
        }
    }
}
