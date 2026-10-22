<?php

declare(strict_types=1);

use function Itineris\SagePay\Tests\submitSagePayForm;

use const Itineris\SagePay\Tests\TEST_CHECKOUT_URL;

it('sends a submitted form to the SagePay checkout with its mapped customer details', function (): void {
    [$result, $sent] = submitSagePayForm('international', [
        1 => '1 Example Street',
        2 => 'Flat 2',
        3 => 'London',
        5 => 'SW1A 1AA',
        6 => 'United Kingdom',
    ]);

    expect($result['confirmation_redirect'] ?? '')->toBe(TEST_CHECKOUT_URL)
        ->and(GFAPI::get_entry($result['entry_id'])['payment_status'])->toBe('Processing')
        ->and($sent)->toMatchArray([
            'Amount' => '10.00',
            'Description' => 'Test payment',
            'CustomerEMail' => 'ada@example.org',
            'BillingFirstnames' => 'Ada',
            'BillingSurname' => 'Lovelace',
            'BillingAddress1' => '1 Example Street',
            'BillingAddress2' => 'Flat 2',
            'BillingCity' => 'London',
            'BillingPostCode' => 'SW1A 1AA',
            'BillingCountry' => 'GB',
            'BillingState' => '',
            'DeliveryFirstnames' => 'Ada',
            'DeliverySurname' => 'Lovelace',
            'DeliveryAddress1' => '1 Example Street',
            'DeliveryCountry' => 'GB',
        ]);
});

it('sends US states as their two-letter code', function (): void {
    [, $sent] = submitSagePayForm('us', [
        1 => '1 Example Avenue',
        3 => 'Los Angeles',
        4 => 'California',
        5 => '90001',
        6 => 'United States',
    ]);

    expect($sent)->toMatchArray([
        'BillingCountry' => 'US',
        'BillingState' => 'CA',
        'DeliveryCountry' => 'US',
        'DeliveryState' => 'CA',
    ]);
});
