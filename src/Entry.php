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

    public function makeAsProcessing(string $uuid, float $amount): void
    {
        $this->setMeta('transaction_uuid', $uuid);
        $this->setProperty('payment_amount', $amount);
        $this->setProperty('payment_status', 'Processing');
        $this->setProperty('is_fulfilled', self::NOT_FULFILLED);
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

    public function markAsPaid(GFPaymentAddOn $addOn, ?string $note = null): void
    {
        $asArray = $this->toArray();

        $addOn->complete_payment(
            $asArray,
            [
                'type' => 'complete_payment',
                'amount' => $this->getProperty('payment_amount'),
                'transaction_id' => $this->getProperty('transaction_id'),
                'entry_id' => $this->getId(),
                'note' => $note,
            ]
        );
    }

    public function toArray(): array
    {
        return $this->data;
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

    public function markAsPending(GFPaymentAddOn $addOn, ?string $note = null): void
    {
        $asArray = $this->toArray();

        $addOn->add_pending_payment(
            $asArray,
            [
                'type' => 'add_pending_payment',
                'amount' => $this->getProperty('payment_amount'),
                'transaction_id' => $this->getProperty('transaction_id'),
                'entry_id' => $this->getId(),
                'note' => $note,
            ]
        );
    }

    public function markAsFailed(GFPaymentAddOn $addOn, ?string $note = null): void
    {
        $asArray = $this->toArray();

        $addOn->fail_payment(
            $asArray,
            [
                'type' => 'fail_payment',
                'amount' => $this->getProperty('payment_amount'),
                'transaction_id' => $this->getProperty('transaction_id'),
                'entry_id' => $this->getId(),
                'note' => $note,
            ]
        );
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
}
