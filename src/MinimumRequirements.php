<?php

declare(strict_types=1);

namespace Itineris\SagePay;

class MinimumRequirements
{
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
