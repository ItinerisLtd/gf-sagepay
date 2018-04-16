<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use Omnipay\Omnipay;
use Omnipay\SagePay\ServerGateway;

class GatewayFactory
{
    public static function buildFromFeed(Feed $feed): ServerGateway
    {
        /* @var ServerGateway $gateway */ // phpcs:ignore
        $gateway = Omnipay::create('SagePay\Server');

        $gateway->setVendor($feed->getVendor());
        $gateway->setTestMode(
            $feed->isTest()
        );

        return $gateway;
    }
}
