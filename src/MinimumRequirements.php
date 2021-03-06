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
                'version' => '4.9.5',
            ],
            'php' => [
                'version' => '7.2',
                'extensions' => [
                    'curl',
                ],
            ],
        ];
    }
}
