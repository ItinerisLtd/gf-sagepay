<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use Omnipay\Omnipay;
use Omnipay\SagePay\ServerGateway;

class GatewayFactory
{
    public static function buildFromFeed(Feed $feed): ServerGateway
    {
        $gateway = self::create();

        $gateway->setVendor(
            (string) $feed->getMeta('vendor')
        );
        $gateway->setTestMode(
            (bool) $feed->getMeta('isTest', true)
        );

        return $gateway;
    }

    public static function create(): ServerGateway
    {
        return Omnipay::create('SagePay\Server');
    }
}
