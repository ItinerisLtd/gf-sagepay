<?php

declare(strict_types=1);

namespace Itineris\SagePay;

class Feed
{
    /**
     * Gravity Forms feed object array
     *
     * @var array
     */
    private $data;

    /**
     * Entry constructor.
     *
     * @param array $data Gravity Forms feed object array.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get a specific property of an array without needing to check if that property exists.
     *
     * Provide a default value if you want to return a specific value if the property is not set.
     *
     * @param string $prop    Name of the property to be retrieved.
     * @param string $default Optional. Value that should be returned if the property is not set or empty. Defaults to
     *                        null.
     *
     * @return null|string|mixed The value
     */
    public function getMeta(string $prop, $default = null)
    {
        return rgar($this->data['meta'] ?? [], $prop, $default);
    }

    public function isActive(): bool
    {
        return (bool) rgar($this->data, 'is_active');
    }

    public function isTest(): bool
    {
        return (bool) $this->getMeta('isTest', true);
    }

    public function getVendor(): string
    {
        return (string) $this->getMeta('vendor');
    }

    public function isAllowGiftAid(): bool
    {
        return 'donation' === $this->getMeta('transactionType');
    }
}
