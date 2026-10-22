<?php

declare(strict_types=1);

namespace Itineris\SagePay\E2E;

use GFAPI;
use RuntimeException;
use WP_Error;

use function array_map;
use function json_encode;
use function range;

/**
 * Builds the e2e site: a SagePay payment form, its feed, and the pages the customer moves through.
 * Load it with `require` from `wp eval`; it prints the form ID and page paths as JSON.
 */

gf_upgrade()->maybe_upgrade();
update_option('rg_gforms_currency', 'GBP');

/**
 * @param array<string, mixed> $page
 */
function createPage(array $page): int
{
    $pageId = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', ...$page], true);
    if ($pageId instanceof WP_Error) {
        throw new RuntimeException($pageId->get_error_message());
    }

    return $pageId;
}

$cancelPageId = createPage(['post_title' => 'Payment cancelled', 'post_content' => 'Your payment was cancelled.']);

$formId = GFAPI::add_form([
    'title' => 'SagePay e2e',
    'fields' => [
        ['id' => 1, 'type' => 'name', 'label' => 'Name', 'nameFormat' => 'advanced', 'inputs' => [
            ['id' => '1.3', 'label' => 'First'],
            ['id' => '1.6', 'label' => 'Last'],
        ]],
        ['id' => 2, 'type' => 'email', 'label' => 'Email'],
        ['id' => 3, 'type' => 'address', 'label' => 'Address', 'addressType' => 'international', 'inputs' => array_map(
            static fn (int $input): array => ['id' => '3.' . $input, 'label' => 'Address ' . $input],
            range(1, 6),
        )],
        ['id' => 4, 'type' => 'product', 'inputType' => 'singleproduct', 'label' => 'Donation', 'basePrice' => '£10.00', 'disableQuantity' => true, 'inputs' => [
            ['id' => '4.1', 'label' => 'Name'],
            ['id' => '4.2', 'label' => 'Price'],
            ['id' => '4.3', 'label' => 'Quantity'],
        ]],
        ['id' => 5, 'type' => 'total', 'label' => 'Total'],
    ],
    'confirmations' => [
        'default' => [
            'id' => 'default',
            'name' => 'Default Confirmation',
            'isDefault' => true,
            'type' => 'message',
            'message' => 'Thank you, your payment was received.',
        ],
    ],
]);
if ($formId instanceof WP_Error) {
    throw new RuntimeException($formId->get_error_message());
}

$feedId = GFAPI::add_feed($formId, [
    'feedName' => 'SagePay e2e',
    'vendor' => 'protxross',
    'isTest' => 'test',
    'transactionType' => 'product',
    'paymentAmount' => 'form_total',
    'description' => 'gf-sagepay e2e',
    'cancelUrl' => get_permalink($cancelPageId),
    'customerInformation_firstName' => '1.3',
    'customerInformation_lastName' => '1.6',
    'customerInformation_email' => '2',
    'billingInformation_address' => '3.1',
    'billingInformation_address2' => '3.2',
    'billingInformation_city' => '3.3',
    'billingInformation_state' => '3.4',
    'billingInformation_zip' => '3.5',
    'billingInformation_country' => '3.6',
    'shippingInformation_address' => '3.1',
    'shippingInformation_address2' => '3.2',
    'shippingInformation_city' => '3.3',
    'shippingInformation_state' => '3.4',
    'shippingInformation_zip' => '3.5',
    'shippingInformation_country' => '3.6',
], 'gf-sagepay');
if ($feedId instanceof WP_Error) {
    throw new RuntimeException($feedId->get_error_message());
}

$payPageId = createPage([
    'post_title' => 'Pay',
    'post_content' => '[gravityform id="' . $formId . '" title="false" ajax="false"]',
]);

echo json_encode([
    'formId' => $formId,
    'payPath' => wp_make_link_relative(get_permalink($payPageId)),
    'cancelPath' => wp_make_link_relative(get_permalink($cancelPageId)),
]);
