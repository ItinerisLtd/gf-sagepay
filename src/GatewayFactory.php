<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use Omnipay\Omnipay;
use Omnipay\SagePay\ServerGateway;

class GatewayFactory
{
    public static function buildFromFeed(Feed $feed): ServerGateway
    {
        /* @var ServerGateway $gateway OmniPay gateway object for SagePay server integration */
        $gateway = Omnipay::create('SagePay\Server');

        $gateway->setVendor(
            (string) $feed->getMeta('vendor')
        );
        $gateway->setTestMode(
            (bool) $feed->getMeta('isTest', true)
        );

        return $gateway;
    }
}
