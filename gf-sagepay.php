<?php

/**
 * Plugin Name:       GF SagePay
 * Plugin URI:        https://github.com/ItinerisLtd/gf-sagepay
 * Description:       SagePay payment gateway for Gravity Forms
 * x-release-please-start-version
 * Version:           1.0.0
 * x-release-please-end
 * Requires at least: 6.7
 * Requires PHP:      8.4.1
 * Author:            Itineris Limited
 * Author URI:        https://www.itineris.co.uk/
 * Text Domain:       gf-sagepay
 */

declare(strict_types=1);

namespace Itineris\SagePay;

use function defined;
use function file_exists;

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

add_action('gform_loaded', function (): void {
    GFSagePay::run();
}, 5);
