<?php

declare(strict_types=1);

/**
 * Keeps the e2e site's notification reply to SagePay free of background loopbacks through the public tunnel.
 */

add_filter('gform_is_asynchronous_notifications_enabled', '__return_false');
