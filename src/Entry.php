<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFAPI;
use GFPaymentAddOn;

class Entry
{
    protected const NOT_FULFILLED = 0;

    /**
     * Gravity Forms entry object array
     *
     * @var array
     */
    protected $data;

    /**
     * Entry constructor.
     *
     * @param array $data Gravity Forms entry object array.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function markAsProcessing(string $uuid, float $amount): void
    {
        $this->setPropertyWithoutReload('transaction_id', $uuid);
        $this->setPropertyWithoutReload('payment_amount', $amount);
        $this->setPropertyWithoutReload('payment_status', 'Processing');
        $this->setPropertyWithoutReload('is_fulfilled', self::NOT_FULFILLED);
        // Workaround for Gravity Forms Encrypted Fields.
        $this->setMetaWithoutReload('transaction_id', $uuid);
        $this->setMetaWithoutReload('payment_amount', $amount);

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
        $result = $this->setPropertyWithoutReload($property, $value);
        $this->reload();

        return $result;
    }

    protected function setPropertyWithoutReload($property, $value): bool
    {
        return (bool) GFAPI::update_entry_property($this->getId(), $property, $value);
    }

    public function getId(): int
    {
        return (int) $this->data['id'];
    }

    protected function reload(): void
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
                'amount' => $this->getMeta('payment_amount'),
                'transaction_id' => $this->getMeta('transaction_id'),
                'entry_id' => $this->getId(),
                'note' => $note,
                'payment_method' => 'SagePay',
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
     * @param string|int $prop Name of the property to be retrieved.
     * @param string     $default Optional. Value that should be returned if the property is not set or empty. Defaults
     *                   to null.
     *
     * @return null|string|mixed The value
     */
    public function getProperty($prop, $default = null)
    {
        return rgar($this->data, (string) $prop, $default);
    }

    public function markAsPending(GFPaymentAddOn $addOn, ?string $note = null): void
    {
        $asArray = $this->toArray();

        $this->setPropertyWithoutReload(
            'transaction_id',
            $this->getMeta('transaction_id')
        );
        $this->setPropertyWithoutReload(
            'payment_amount',
            $this->getMeta('payment_amount')
        );
        $this->reload();

        $addOn->add_pending_payment(
            $asArray,
            [
                'type' => 'add_pending_payment',
                'amount' => $this->getMeta('payment_amount'),
                'transaction_id' => $this->getMeta('transaction_id'),
                'entry_id' => $this->getId(),
                'note' => $note,
                'payment_method' => 'SagePay',
            ]
        );

        $this->reload();
    }

    public function markAsFailed(GFPaymentAddOn $addOn, ?string $note = null): void
    {
        $asArray = $this->toArray();

        $this->setPropertyWithoutReload(
            'transaction_id',
            $this->getMeta('transaction_id')
        );
        $this->setPropertyWithoutReload(
            'payment_amount',
            $this->getMeta('payment_amount')
        );
        $this->reload();

        $addOn->fail_payment(
            $asArray,
            [
                'type' => 'fail_payment',
                'amount' => $this->getMeta('payment_amount'),
                'transaction_id' => $this->getMeta('transaction_id'),
                'entry_id' => $this->getId(),
                'note' => $note,
                'payment_method' => 'SagePay',
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
        $this->setMetaWithoutReload($key, $value);
        $this->reload();
    }

    protected function setMetaWithoutReload($key, $value): void
    {
        gform_update_meta($this->getId(), $key, $value);
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

    public function isTimeout(): bool
    {
        $dateCreated = strtotime($this->getProperty('date_created'));

        if (! is_int($dateCreated)) {
            return false;
        }

        return time() - $dateCreated > 1800; // 30 minutes.
    }
}
