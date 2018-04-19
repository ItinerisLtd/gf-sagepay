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

    public function getFormId(): int
    {
        return (int) rgar($this->data, 'form_id');
    }

    public function isActive(): bool
    {
        return (bool) rgar($this->data, 'is_active');
    }

    public function isTest(): bool
    {
        return (bool) $this->getMeta('isTest', true);
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
        return rgars($this->data, 'meta/' . $prop, $default);
    }

    public function getVendor(): string
    {
        $vendor = (string) $this->getMeta('vendor');

        if ('gf_custom' === $vendor) {
            return (string) $this->getMeta('vendor_custom');
        }

        return $vendor;
    }

    public function isAllowGiftAid(): bool
    {
        return 'donation' === $this->getMeta('transactionType');
    }
}
