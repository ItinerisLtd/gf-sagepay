<?php

declare(strict_types=1);

namespace Itineris\SagePay;

class MinimumRequirements
{
    public const GRAVITY_FORMS_VERSION = '2.4.14.4';

    public static function toArray(): array
    {
        return [
            'wordpress' => [
                'version' => '6.7',
            ],
            'php' => [
                'version' => '8.4',
                'extensions' => [
                    'curl',
                ],
            ],
        ];
    }
}
