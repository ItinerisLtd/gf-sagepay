<?php

/**
 * Plugin Name:       GF SagePay
 * Plugin URI:        https://github.com/ItinerisLtd/gf-sagepay
 * Description:       SagePay payment gateway for Gravity Forms
 * Version:           0.11.0
 * Requires at least: 4.9.5
 * Requires PHP:      7.2
 * Author:            Itineris Limited
 * Author URI:        https://www.itineris.co.uk/
 * Text Domain:       gf-sagepay
 */

declare(strict_types=1);

namespace Itineris\SagePay;

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

GFSagePay::run();
