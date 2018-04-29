<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFAPI;
use GFPaymentAddOn;

class Entry
{
    private const NOT_FULFILLED = 0;

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
        $this->setProperty('transaction_id', $uuid);
        $this->setProperty('payment_amount', $amount);
        $this->setProperty('payment_status', 'Processing');
        $this->setProperty('is_fulfilled', self::NOT_FULFILLED);

        $this->reload();
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
        $result = (bool) GFAPI::update_entry_property($this->getId(), $property, $value);
        $this->reload();

        return $result;
    }

    public function getId(): int
    {
        return (int) $this->data['id'];
    }

    private function reload(): void
    {
        $this->data = GFAPI::get_entry($this->getId());
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

        $this->reload();
    }

    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get a specific property of an array without needing to check if that property exists.
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

        $this->reload();
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

        $this->reload();
    }

    public function getFormId(): int
    {
        return (int) rgar($this->data, 'form_id');
    }

    public function getConfirmationTokenHash(): string
    {
        return (string) $this->getMeta('gf_sagepay_token_hash');
    }

    public function getMeta(string $key)
    {
        return gform_get_meta($this->getId(), $key);
    }

    public function setConfirmationTokenHash(string $confirmationToken, int $expiredAt): void
    {
        $this->setMeta('gf_sagepay_token_hash', $confirmationToken);
        $this->setMeta('gf_sagepay_token_expired_at', $expiredAt);
    }

    /**
     * Add or update metadata associated with an entry.
     * Data will be serialized. Don't forget to sanitize user input.
     *
     * @param string $key   The key for the meta data to be stored.
     * @param mixed  $value The data to be stored for the entry.
     */
    public function setMeta($key, $value): void
    {
        gform_update_meta($this->getId(), $key, $value);

        $this->reload();
    }

    public function expireConfirmationTokenNow(): void
    {
        $this->setMeta('gf_sagepay_token_expired_at', 0);
    }

    public function getConfirmationTokenExpiredAt(): int
    {
        return (int) $this->getMeta('gf_sagepay_token_expired_at');
    }

    public function isPaidOrPending(): bool
    {
        return in_array(
            $this->getProperty('payment_status', 'Failed'),
            ['Paid', 'Pending'],
            true
        );
    }
}
