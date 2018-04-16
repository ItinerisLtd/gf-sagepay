<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFAPI;
use GFPaymentAddOn;

class Entry
{
    private const NOT_FULFILLED = 0;
    private const FULFILLED = 1;

    /**
     * Gravity Forms entry object array
     *
     * @var array
     */
    private $data;

    /**
     * Entry constructor.
     *
     * @param array $data Gravity Forms entry object array.
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
    public function getProperty(string $prop, $default = null)
    {
        return rgar($this->data, $prop, $default);
    }

    public function makeAsProcessing(string $uuid, float $amount): void
    {
        $this->setMeta('transaction_uuid', $uuid);
        $this->setProperty('payment_amount', $amount);
        $this->setProperty('payment_status', 'Processing');
        $this->setProperty('is_fulfilled', self::NOT_FULFILLED);
    }

    public function markAsFailed(GFPaymentAddOn $addOn, string $note): void
    {
        $this->setProperty('payment_status', 'Failed');

        $addOn->add_note(
            $this->getId(),
            $note
        );

        $addOn->log_error($note);

        $addOn->post_payment_action(
            $this->toArray(),
            [
                'type' => 'fail_payment',
                'amount' => $this->getProperty('payment_amount'),
                'transaction_type' => $this->getProperty('transaction_type'),
                'transaction_id' => $this->getProperty('transaction_id'),
                'entry_id' => $this->getId(),
                'payment_status' => $this->getProperty('payment_status'),
                'note' => $note,
            ]
        );
    }

    /**
     * Add or update metadata associated with an entry.
     *
     * Data will be serialized. Don't forget to sanitize user input.
     *
     * @param string $key   The key for the meta data to be stored.
     * @param mixed  $value The data to be stored for the entry.
     */
    public function setMeta($key, $value): void
    {
        gform_update_meta($this->getId(), $key, $value);
    }

    public function getId(): int
    {
        return $this->data['id'];
    }

    /**
     * Updates a single property of an entry.
     *
     * @param string $property The property of the Entry object to be updated.
     * @param mixed  $value    The value to which the property should be set.
     *
     * @return bool Whether the entry property was updated successfully.
     */
    public function setProperty($property, $value): bool
    {
        return GFAPI::update_entry_property($this->getId(), $property, $value);
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
        return rgar($this->data, $prop, $default);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
