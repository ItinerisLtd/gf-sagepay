<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GF_Field_Address;
use GF_Fields;
use Omnipay\Common\CreditCard;

class CreditCardFactory
{
    public static function build(Feed $feed, Entry $entry): CreditCard
    {
        /* @var GF_Field_Address $addressFiled Field object for address formatting */
        $addressFiled = GF_Fields::get('address');

        $billingCountry = $addressFiled->get_country_code(
            $entry->getProperty(
                $feed->getMeta('billingInformation_country')
            )
        );
        $billingState = $addressFiled->get_us_state_code(
            $entry->getProperty(
                $feed->getMeta('billingInformation_state')
            )
        );
        $shippingCountry = $addressFiled->get_country_code(
            $entry->getProperty(
                $feed->getMeta('shippingInformation_country')
            )
        );
        $shippingState = $addressFiled->get_us_state_code(
            $entry->getProperty(
                $feed->getMeta('shippingInformation_state')
            )
        );

        return new CreditCard(
            array_filter([
                'firstName' => $entry->getProperty(
                    $feed->getMeta('customerInformation_firstName')
                ),
                'lastName' => $entry->getProperty(
                    $feed->getMeta('customerInformation_lastName')
                ),
                'email' => $entry->getProperty(
                    $feed->getMeta('customerInformation_email')
                ),
                'billingPhone' => $entry->getProperty(
                    $feed->getMeta('customerInformation_phone')
                ),

                'billingAddress1' => $entry->getProperty(
                    $feed->getMeta('billingInformation_address')
                ),
                'billingAddress2' => $entry->getProperty(
                    $feed->getMeta('billingInformation_address2')
                ),
                'billingCity' => $entry->getProperty(
                    $feed->getMeta('billingInformation_city')
                ),
                'billingPostcode' => $entry->getProperty(
                    $feed->getMeta('billingInformation_zip')
                ),
                'billingCountry' => $billingCountry,
                'billingState' => $billingState,

                'shippingAddress1' => $entry->getProperty(
                    $feed->getMeta('shippingInformation_address')
                ),
                'shippingAddress2' => $entry->getProperty(
                    $feed->getMeta('shippingInformation_address2')
                ),
                'shippingCity' => $entry->getProperty(
                    $feed->getMeta('shippingInformation_city')
                ),
                'shippingPostcode' => $entry->getProperty(
                    $feed->getMeta('shippingInformation_zip')
                ),
                'shippingCountry' => $shippingCountry,
                'shippingState' => $shippingState,
            ])
        );
    }
}
