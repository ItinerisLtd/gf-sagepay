<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use Omnipay\Omnipay;
use Omnipay\SagePay\ServerGateway;

class GatewayFactory
{
    public static function buildFromFeed(Feed $feed): ServerGateway
    {
        return self::build(
            $feed->getVendor(),
            $feed->isTest(),
        );
    }

    /**
     * A gateway for parsing notifications. The vendor code is set on each notification once its entry is known.
     */
    public static function buildForNotification(): NotifyGateway
    {
        return new NotifyGateway();
    }

    public static function build(string $vendor, bool $isTest): ServerGateway
    {
        /** @var ServerGateway $gateway */
        $gateway = Omnipay::create('SagePay\Server');

        $gateway->setVendor($vendor);
        $gateway->setTestMode($isTest);

        return $gateway;
    }
}
