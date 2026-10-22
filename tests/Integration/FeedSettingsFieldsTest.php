<?php

declare(strict_types=1);

use Itineris\SagePay\AddOn;
use Itineris\SagePay\FeedSettingsFields;

use function Itineris\SagePay\Tests\createSagePayForm;

use const Itineris\SagePay\Tests\TEST_VENDOR;

it('offers each vendor code already used by a feed once', function (): void {
    createSagePayForm();
    createSagePayForm();
    createSagePayForm(['vendor' => 'gf_custom', 'vendor_custom' => 'customvendor']);

    $fields = array_merge(...array_column(FeedSettingsFields::toArray(AddOn::get_instance()), 'fields'));
    $vendorField = current(array_filter($fields, static fn (array $field): bool => 'vendor' === ($field['name'] ?? '')));

    expect($vendorField)->toBeArray()
        ->and(array_column($vendorField['choices'], 'label'))
        ->toBe(['Select a Vendor Code', TEST_VENDOR, 'customvendor']);
});
