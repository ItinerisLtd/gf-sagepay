<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use Omnipay\SagePay\ServerGateway;

/**
 * Parses notifications into NotifyRequest, so every signature check on them is constant time.
 */
final class NotifyGateway extends ServerGateway
{
    public function acceptNotification(array $parameters = []): NotifyRequest
    {
        /** @var NotifyRequest $request */
        $request = $this->createRequest(NotifyRequest::class, $parameters);

        return $request;
    }
}
