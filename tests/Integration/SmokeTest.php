<?php

declare(strict_types=1);

use Itineris\SagePay\AddOn;
use Itineris\SagePay\Terminator;
use Mantle\Testing\Exceptions\Exit_Simulation_Exception;

it('loads Gravity Forms and creates its tables', function (): void {
    /** @var wpdb $wpdb */
    $wpdb = $GLOBALS['wpdb'];

    expect(class_exists(GFForms::class))->toBeTrue()
        ->and($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->prefix . 'gf_entry')))
        ->toBe($wpdb->prefix . 'gf_entry');
});

it('registers the SagePay add-on with Gravity Forms', function (): void {
    expect(GFAddOn::get_registered_addons())->toContain(AddOn::class)
        ->and(AddOn::get_instance())->toBeInstanceOf(GFPaymentAddOn::class);
});

it('rejects an empty notification', function (): void {
    $this->post('/?callback=gf-sagepay', [])->assertSee('Status=INVALID');
});

it('ends requests by throwing instead of exiting under test', function (): void {
    expect(fn () => Terminator::terminate())->toThrow(Exit_Simulation_Exception::class);
});
