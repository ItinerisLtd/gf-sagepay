<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFAPI;
use GFPaymentAddOn;

use function in_array;
use function is_int;
use function strtotime;
use function time;

class Entry
{
    protected const NOT_FULFILLED = 0;

    /**
     * Gravity Forms entry object array
     *
     * @var array
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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
     * Updates a single property of an entry to the given value.
     *
     * @param string $property The property of the Entry object to be updated.
     *
     * @return bool Whether the entry property was updated successfully.
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint $value
     */
    public function setProperty(string $property, $value): bool
    {
        $result = $this->setPropertyWithoutReload($property, $value);
        $this->reload();

        return $result;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint $value
     */
    protected function setPropertyWithoutReload(string $property, $value): bool
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
            ],
        );

        $this->reload();
    }

    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get a specific property of the entry without needing to check if that property exists.
     * $prop is the property name or field ID, usually a feed field mapping, and may be null when unmapped.
     * $default is returned instead when the property is not set or empty.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint $prop
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint $default
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
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
            $this->getMeta('transaction_id'),
        );
        $this->setPropertyWithoutReload(
            'payment_amount',
            $this->getMeta('payment_amount'),
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
            ],
        );

        $this->reload();
    }

    public function markAsFailed(GFPaymentAddOn $addOn, ?string $note = null): void
    {
        $asArray = $this->toArray();

        $this->setPropertyWithoutReload(
            'transaction_id',
            $this->getMeta('transaction_id'),
        );
        $this->setPropertyWithoutReload(
            'payment_amount',
            $this->getMeta('payment_amount'),
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
            ],
        );

        $this->reload();
    }

    public function getFormId(): int
    {
        return (int) rgar($this->data, 'form_id');
    }

    /**
     * The vendor code the payment was registered with.
     */
    public function getVendor(): string
    {
        return (string) $this->getMeta('gf_sagepay_vendor');
    }

    public function getConfirmationTokenHash(): string
    {
        return (string) $this->getMeta('gf_sagepay_token_hash');
    }

    public function getUsedConfirmationTokenHash(): string
    {
        return (string) $this->getMeta('gf_sagepay_token_used_hash');
    }

    /**
     * Records the hash of the consumed token, so a refresh of the same link can be recognised.
     */
    public function setUsedConfirmationTokenHash(string $hash): bool
    {
        $result = gform_update_meta($this->getId(), 'gf_sagepay_token_used_hash', $hash);

        return false !== $result && null !== $result;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
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
     * @param string $key The key for the meta data to be stored.
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint $value
     */
    public function setMeta(string $key, $value): void
    {
        $this->setMetaWithoutReload($key, $value);
        $this->reload();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint $value
     */
    protected function setMetaWithoutReload(string $key, $value): void
    {
        gform_update_meta($this->getId(), $key, $value);
    }

    /**
     * @deprecated The confirmation flow consumes the token with deleteConfirmationTokenHash().
     */
    public function expireConfirmationTokenNow(): void
    {
        $this->setMeta('gf_sagepay_token_expired_at', 0);
    }

    /**
     * Deletes the confirmation token hash in a single query, so only one request can consume a token.
     * Returns the number of rows deleted, or false/null when Gravity Forms could not run the query.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    public function deleteConfirmationTokenHash()
    {
        return gform_delete_meta($this->getId(), 'gf_sagepay_token_hash');
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
            true,
        );
    }

    /**
     * @deprecated The confirmation flow trusts the payment status SagePay reported, whatever the entry's age.
     */
    public function isTimeout(): bool
    {
        $dateCreated = strtotime($this->getProperty('date_created'));

        if (! is_int($dateCreated)) {
            return false;
        }

        return time() - $dateCreated > 1800; // 30 minutes.
    }
}
