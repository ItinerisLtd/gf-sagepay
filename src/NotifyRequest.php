<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use Omnipay\SagePay\Message\ServerNotifyRequest;

use function hash_equals;

/**
 * A SagePay Server notification whose signature is compared in constant time; omnipay's own check uses ===.
 */
final class NotifyRequest extends ServerNotifyRequest
{
    public function isValid(): bool
    {
        return hash_equals($this->buildSignature(), $this->getSignature());
    }
}
