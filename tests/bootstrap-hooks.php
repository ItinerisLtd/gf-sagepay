<?php

declare(strict_types=1);

use Itineris\SagePay\Tests\TestCase;
use Mantle\Testing\Exceptions\Exit_Simulation_Exception;

register_shutdown_function(static function (): void {
    if (! TestCase::$running) {
        return;
    }

    fwrite(STDERR, "\nA test ended the PHP process with exit; failing the run.\n");
    exit(1);
});

// Requests end by throwing instead of exiting, so tests can assert on the reply.
add_action('gf_sagepay_terminate', static function (): never {
    throw new Exit_Simulation_Exception();
});

// Send notifications inline rather than through Gravity Forms' background processor.
add_filter('gform_is_asynchronous_notifications_enabled', '__return_false');

// Gravity Forms creates its tables on init. Fail loudly if it didn't, and start each run from empty tables.
add_action('init', static function (): void {
    /** @var wpdb $wpdb */
    $wpdb = $GLOBALS['wpdb'];
    $entryTable = $wpdb->prefix . 'gf_entry';

    if ($entryTable !== $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $entryTable))) {
        throw new RuntimeException('Gravity Forms tables were not installed.');
    }

    foreach ($wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($wpdb->prefix . 'gf_') . '%')) as $table) {
        $wpdb->query('TRUNCATE TABLE `' . esc_sql($table) . '`');
    }
}, 11);
