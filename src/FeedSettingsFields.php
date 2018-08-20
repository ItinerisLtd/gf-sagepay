<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFFeedAddOn;
use Omnipay\SagePay\Message\AbstractRequest;

class FeedSettingsFields
{
    public static function toArray(GFFeedAddOn $addOn): array
    {
        $feedNameTooltip =
            '<h6>' .
            esc_html__('Name', 'gf-sagepay') .
            '</h6>' .
            esc_html__('Enter a feed name to uniquely identify this setup.', 'gf-sagepay');

        $transactionTypeTooltip =
            '<h6>' .
            esc_html__('Transaction Type', 'gf-sagepay') .
            '</h6>' .
            esc_html__('Select a transaction type.', 'gf-sagepay');

        $paymentAmountTooltip =
            '<h6>' .
            esc_html__('Payment Amount', 'gf-sagepay') .
            '</h6>' .
            esc_html__(
                "Select which field determines the payment amount, or select 'Form Total' to use the total of all pricing fields as the payment amount.",
                'gf-sagepay'
            );

        $conditionalLogicTooltip =
            '<h6>' .
            esc_html__('Conditional Logic', 'gf-sagepay') .
            '</h6>' .
            esc_html__(
                'When conditions are enabled, form submissions will only be sent to the payment gateway when the conditions are met. When disabled, all form submissions will be sent to the payment gateway.',
                'gf-sagepay'
            );

        return [
            [
                'fields' => [
                    [
                        'name' => 'feedName',
                        'label' => esc_html__('Name', 'gf-sagepay'),
                        'type' => 'text',
                        'class' => 'medium',
                        'required' => true,
                        'tooltip' => $feedNameTooltip,
                    ],
                    [
                        'name' => 'transactionType',
                        'label' => esc_html__('Transaction Type', 'gf-sagepay'),
                        'type' => 'select',
                        'onchange' => "jQuery(this).parents('form').submit();",
                        'choices' => [
                            [
                                'label' => esc_html__('Select a transaction type', 'gf-sagepay'),
                                'value' => '',
                            ],
                            [
                                'label' => esc_html__('Products and Services', 'gf-sagepay'),
                                'value' => 'product',
                            ],
                            [
                                'label' => esc_html__('Donation', 'gf-sagepay'),
                                'value' => 'donation',
                            ],
                        ],
                        'tooltip' => $transactionTypeTooltip,
                    ],
                ],
            ],
            [
                'title' => esc_html__('SagePay Settings', 'gf-sagepay'),
                'dependency' => [
                    'field' => 'transactionType',
                    'values' => [
                        'subscription',
                        'product',
                        'donation',
                    ],
                ],
                'fields' => self::sagePaySettingsFields($addOn),
            ],
            [
                'title' => esc_html__('Products &amp; Services Settings', 'gf-sagepay'),
                'dependency' => [
                    'field' => 'transactionType',
                    'values' => [
                        'product',
                        'donation',
                    ],
                ],
                'fields' => [
                    [
                        'name' => 'paymentAmount',
                        'label' => esc_html__('Payment Amount', 'gf-sagepay'),
                        'type' => 'select',
                        'choices' => $addOn->product_amount_choices(),
                        'required' => true,
                        'default_value' => 'form_total',
                        'tooltip' => $paymentAmountTooltip,
                    ],
                ],
            ],
            [
                'title' => esc_html__('Order Settings', 'gf-sagepay'),
                'dependency' => [
                    'field' => 'transactionType',
                    'values' => [
                        'subscription',
                        'product',
                        'donation',
                    ],
                ],
                'fields' => self::orderSettingsFields(),
            ],
            [
                'title' => esc_html__('Other Settings', 'gf-sagepay'),
                'dependency' => [
                    'field' => 'transactionType',
                    'values' => [
                        'subscription',
                        'product',
                        'donation',
                    ],
                ],
                'fields' => [
                    [
                        'name' => 'conditionalLogic',
                        'label' => esc_html__('Conditional Logic', 'gf-sagepay'),
                        'type' => 'feed_condition',
                        'tooltip' => $conditionalLogicTooltip,
                    ],
                ],
            ],
        ];
    }

    private static function sagePaySettingsFields(GFFeedAddOn $addOn): array
    {
        $venforTooltip =
            esc_html__(
                'Used to authenticate your site. This should contain the Sage Pay Vendor Name supplied by Sage Pay when your account was created.',
                'gf-sagepay'
            );

        $cancelUrlTooltip =
            esc_html__(
                'Enter the URL the user should be sent to if they cancelled the SagePay checkout form or payment failed.',
                'gf-sagepay'
            );

        return [
            [
                'type' => 'select_custom',
                'name' => 'vendor',
                'label' => esc_html__('Vendor Code', 'gf-sagepay'),
                'required' => true,
                'choices' => self::getAllVendors($addOn),
                'after_input' => esc_html__('Letters (A-Z and a-z) and Numbers(0-9)', 'gf-sagepay'),
                'tooltip' => $venforTooltip,
            ],
            [
                'type' => 'text',
                'name' => 'description',
                'label' => esc_html__('Description', 'gf-sagepay'),
                'required' => true,
                'tooltip' => esc_html__('A brief description of the goods or services purchased.', 'gf-sagepay'),
            ],
            [
                'name' => 'isTest',
                'label' => esc_html__('Environment', 'gf-sagepay'),
                'required' => true,
                'type' => 'radio',
                'default_value' => 'test',
                'choices' => [
                    [
                        'label' => esc_html__('Live', 'gf-sagepay'),
                        'value' => 'production',
                    ],
                    [
                        'label' => esc_html__('Test', 'gf-sagepay'),
                        'value' => 'test',
                    ],
                ],
            ],
            [
                'name' => '3dSecure',
                'label' => esc_html__('3D-Secure', 'gf-sagepay'),
                'required' => true,
                'type' => 'radio',
                'default_value' => AbstractRequest::APPLY_3DSECURE_APPLY,
                'choices' => [
                    [
                        'label' => esc_html__('Use MSP Setting', 'gf-sagepay'),
                        'tooltip' => esc_html__('Use default MySagePay settings.', 'gf-sagepay'),
                        'value' => AbstractRequest::APPLY_3DSECURE_APPLY,
                    ],
                    [
                        'label' => esc_html__('Force', 'gf-sagepay'),
                        'tooltip' => esc_html__('Apply authentication even if turned off.', 'gf-sagepay'),
                        'value' => AbstractRequest::APPLY_3DSECURE_FORCE,
                    ],
                    [
                        'label' => esc_html__('Disable', 'gf-sagepay'),
                        'tooltip' => esc_html__('Disable authentication and rules.', 'gf-sagepay'),
                        'value' => AbstractRequest::APPLY_3DSECURE_NONE,
                    ],
                    [
                        'label' => esc_html__('Force Ignoring Rules', 'gf-sagepay'),
                        'tooltip' => esc_html__('Apply authentication but ignore rules.', 'gf-sagepay'),
                        'value' => AbstractRequest::APPLY_3DSECURE_AUTH,
                    ],
                ],
            ],
            [
                'name' => 'avscv2',
                'label' => esc_html__('AVS/CV2', 'gf-sagepay'),
                'required' => true,
                'type' => 'radio',
                'default_value' => AbstractRequest::APPLY_AVSCV2_DEFAULT,
                'choices' => [
                    [
                        'label' => esc_html__('Use MSP Setting', 'gf-sagepay'),
                        'tooltip' => esc_html__('Use default MySagePay settings.', 'gf-sagepay'),
                        'value' => AbstractRequest::APPLY_AVSCV2_DEFAULT,
                    ],
                    [
                        'label' => esc_html__('Force', 'gf-sagepay'),
                        'tooltip' => esc_html__('Apply authentication even if turned off.', 'gf-sagepay'),
                        'value' => AbstractRequest::APPLY_AVSCV2_FORCE_CHECKS,
                    ],
                    [
                        'label' => esc_html__('Disable', 'gf-sagepay'),
                        'tooltip' => esc_html__('Disable authentication and rules.', 'gf-sagepay'),
                        'value' => AbstractRequest::APPLY_AVSCV2_NO_CHECKS,
                    ],
                    [
                        'label' => esc_html__('Force Ignoring Rules', 'gf-sagepay'),
                        'tooltip' => esc_html__('Apply authentication but ignore rules.', 'gf-sagepay'),
                        'value' => AbstractRequest::APPLY_AVSCV2_NO_RULES,
                    ],
                ],
            ],
            [
                'type' => 'text',
                'name' => 'cancelUrl',
                'label' => esc_html__('Cancel URL', 'gf-sagepay'),
                'class' => 'large',
                'after_input' => esc_html__('Leave blank to use Gravity Forms confirmations', 'gf-sagepay'),
                'tooltip' => $cancelUrlTooltip,
            ],
        ];
    }

    private static function getAllVendors(GFFeedAddOn $addOn): array
    {
        $rawFeeds = $addOn->get_feeds();

        $feeds = array_map(function (array $rawFeed): Feed {
            return new Feed($rawFeed);
        }, $rawFeeds);

        $allVendors = array_map(function (Feed $feed): string {
            return $feed->getVendor();
        }, $feeds);

        $uniqueVendors = array_unique(
            array_filter($allVendors)
        );

        $choices = array_map(function (string $vendor): array {
            return ['label' => $vendor];
        }, $uniqueVendors);

        return array_merge([
            [
                'value' => '',
                'label' => 'Select a Vendor Code',
            ],
        ], $choices);
    }

    private static function orderSettingsFields(): array
    {
        $customerInformationTooltip =
            '<h6>' .
            esc_html__('Customer Information', 'gf-sagepay') .
            '</h6>' .
            esc_html__('Map your Form Fields to the available listed fields.', 'gf-sagepay');

        $billingInformationTooltip =
            '<h6>' .
            esc_html__('Billing Information', 'gf-sagepay') .
            '</h6>' .
            esc_html__('Map your Form Fields to the available listed fields.', 'gf-sagepay');

        $shippingInformationTooltip =
            '<h6>' .
            esc_html__('Shipping Information', 'gf-sagepay') .
            '</h6>' .
            esc_html__('Map your Form Fields to the available listed fields.', 'gf-sagepay');

        return [
            [
                'name' => 'customerInformation',
                'label' => esc_html__('Customer Information', 'gf-sagepay'),
                'type' => 'field_map',
                'field_map' => self::customerInfoFields(),
                'tooltip' => $customerInformationTooltip,
            ],
            [
                'name' => 'billingInformation',
                'label' => esc_html__('Billing Information', 'gf-sagepay'),
                'type' => 'field_map',
                'field_map' => self::addressFields(),
                'tooltip' => $billingInformationTooltip,
            ],
            [
                'name' => 'shippingInformation',
                'label' => esc_html__('Shipping Information', 'gf-sagepay'),
                'type' => 'field_map',
                'field_map' => self::addressFields(),
                'tooltip' => $shippingInformationTooltip,
            ],
        ];
    }

    private static function customerInfoFields(): array
    {
        return [
            [
                'name' => 'firstName',
                'label' => esc_html__('First Name', 'gf-sagepay'),
                'required' => true,
            ],
            [
                'name' => 'lastName',
                'label' => esc_html__('Last Name', 'gf-sagepay'),
                'required' => true,
            ],
            [
                'name' => 'email',
                'label' => esc_html__('Email', 'gf-sagepay'),
                'required' => false,
            ],
            [
                'name' => 'phone',
                'label' => esc_html__('Phone', 'gf-sagepay'),
                'required' => false,
            ],
        ];
    }

    private static function addressFields(): array
    {
        return [
            [
                'name' => 'address',
                'label' => esc_html__('Address', 'gf-sagepay'),
                'required' => true,
            ],
            [
                'name' => 'address2',
                'label' => esc_html__('Address 2', 'gf-sagepay'),
                'required' => false,
            ],
            [
                'name' => 'city',
                'label' => esc_html__('City', 'gf-sagepay'),
                'required' => true,
            ],
            [
                'name' => 'zip',
                'label' => esc_html__('Zip', 'gf-sagepay'),
                'required' => true,
            ],
            [
                'name' => 'country',
                'label' => esc_html__('Country', 'gf-sagepay'),
                'required' => true,
            ],
            [
                'name' => 'state',
                'label' => esc_html__('State', 'gf-sagepay'),
                'required' => false,
            ],
        ];
    }
}
