<?php

declare(strict_types=1);

namespace Itineris\SagePay;

class SupportedNotificationEvents
{
    public static function toArray(): array
    {
        return [
            'complete_payment' => esc_html__('Payment Completed', 'gf-sagepay'),
            'add_pending_payment' => esc_html__('Payment Pending', 'gf-sagepay'),
            'fail_payment' => esc_html__('Payment Failed', 'gf-sagepay'),
        ];
    }
}
