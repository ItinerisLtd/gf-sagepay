<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFAPI;
use GFPaymentAddOn;
use Omnipay\Common\Message\NotificationInterface;
use Omnipay\SagePay\Message\ServerNotifyRequest;
use Throwable;
use wpdb;

use function hash_equals;
use function is_array;
use function is_string;
use function json_decode;

/**
 * @internal
 */
final class CallbackHandler
{
    public static function run(GFPaymentAddOn $addOn): never
    {
        $request = GatewayFactory::buildForNotification()->acceptNotification();

        // Nothing that terminates may run inside these try blocks: a throwing terminate listener would be
        // caught and a second reply sent.
        try {
            $entry = self::getVerifiedEntry($request, $addOn);
        } catch (Throwable $exception) {
            self::logException(__METHOD__, $exception, $addOn);
            $entry = 'Unable to process notification';
        }

        if (! $entry instanceof Entry) {
            self::reject($request, $addOn, $entry);
        }

        // The notification is genuine from here on, so SagePay always gets an OK reply.
        try {
            self::applyStatus($request, $entry, $addOn);
            $nextUrl = self::getNextUrl($entry);
        } catch (Throwable $exception) {
            self::logException(__METHOD__, $exception, $addOn);
            $nextUrl = self::getFallbackNextUrl();
        }

        $addOn->log_debug(__METHOD__ . '(): Confirm entry ' . $entry->getId());
        $request->confirm($nextUrl);
        Terminator::terminate();
    }

    /**
     * Matches the notification to an entry and verifies it, without touching the entry.
     *
     * @return Entry|string The verified entry, or the reason the notification is rejected.
     */
    private static function getVerifiedEntry(ServerNotifyRequest $request, GFPaymentAddOn $addOn): Entry|string
    {
        if (! self::hasOnlyStringFields($request)) {
            return 'Malformed notification';
        }

        $transactionId = $request->getTransactionId();
        if (! is_string($transactionId) || ! wp_is_uuid($transactionId)) {
            return 'Unknown transaction';
        }

        $entry = self::getEntryByTransactionId($transactionId, $addOn);
        if (! $entry instanceof Entry) {
            return 'Unknown transaction';
        }

        $reference = $entry->getMeta('transaction_reference');
        if (! self::isUsableReference($reference, $transactionId)) {
            return 'Unusable transaction reference for entry ' . $entry->getId();
        }

        $vendor = self::getVendor($entry, $addOn);
        if ('' === $vendor) {
            return 'No vendor code for entry ' . $entry->getId();
        }

        $addOn->log_debug(__METHOD__ . '(): Verifying entry ' . $entry->getId() . ' with vendor - ' . $vendor);
        $request->setVendor($vendor);
        $request->setTransactionReference($reference);

        if (! $request->isValid()) {
            return 'Signature not valid for entry ' . $entry->getId();
        }

        self::logDebug(__METHOD__, $request, $addOn);

        return $entry;
    }

    private static function reject(ServerNotifyRequest $request, GFPaymentAddOn $addOn, string $message): never
    {
        $addOn->log_error(__METHOD__ . '(): ' . $message);
        $request->invalid(
            self::getFallbackNextUrl(),
            $message,
        );
        Terminator::terminate();
    }

    /**
     * The vendor code the payment was registered with, or the feed's for entries registered without one stored.
     */
    private static function getVendor(Entry $entry, GFPaymentAddOn $addOn): string
    {
        $vendor = $entry->getVendor();
        if ('' !== $vendor) {
            return $vendor;
        }

        $feed = Feed::forEntry($addOn, $entry);

        return $feed instanceof Feed ? $feed->getVendor() : '';
    }

    private static function hasOnlyStringFields(ServerNotifyRequest $request): bool
    {
        foreach ($request->getData() as $value) {
            if (! is_string($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The stored reference must belong to this transaction and include its SecurityKey.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint $reference
     */
    private static function isUsableReference($reference, string $transactionId): bool
    {
        if (! is_string($reference)) {
            return false;
        }

        $parts = json_decode($reference, true);

        return is_array($parts)
            && is_string($parts['SecurityKey'] ?? null)
            && '' !== $parts['SecurityKey']
            && is_string($parts['VendorTxCode'] ?? null)
            && hash_equals($parts['VendorTxCode'], $transactionId);
    }

    /**
     * Applies the notified status without repeating it, and never downgrades a paid entry.
     */
    private static function applyStatus(ServerNotifyRequest $request, Entry $entry, GFPaymentAddOn $addOn): void
    {
        $status = $request->getTransactionStatus();
        $targetStatus = match ($status) {
            NotificationInterface::STATUS_COMPLETED => 'Paid',
            NotificationInterface::STATUS_PENDING => 'Pending',
            default => 'Failed',
        };
        $currentStatus = $entry->getProperty('payment_status');

        if ($targetStatus === $currentStatus || 'Paid' === $currentStatus) {
            $addOn->log_debug(
                __METHOD__ . '(): Entry ' . $entry->getId() . ' already ' . $currentStatus . ', ignoring ' . $status,
            );
            return;
        }

        // Save the final transactionReference against the transaction in the database. It will
        // be needed if you want to capture the payment (for an authorize) or void or refund or
        // repeat the payment later.
        $entry->setMeta(
            'final_transaction_reference',
            $request->getTransactionReference(),
        );

        match ($targetStatus) {
            'Paid' => $entry->markAsPaid($addOn),
            'Pending' => $entry->markAsPending($addOn),
            default => $entry->markAsFailed($addOn),
        };
    }

    private static function logException(string $methodName, Throwable $exception, GFPaymentAddOn $addOn): void
    {
        $addOn->log_error(
            $methodName . '(): ' . $exception->getMessage()
            . ' in ' . $exception->getFile() . ':' . $exception->getLine(),
        );
    }

    /**
     * Log the notification via Gravity Forms logger, without the signature.
     */
    private static function logDebug(string $methodName, ServerNotifyRequest $request, GFPaymentAddOn $addOn): void
    {
        $data = $request->getData();
        unset($data['VPSSignature']);

        $addOn->log_debug($methodName . '(): Status - ' . $request->getTransactionStatus());
        $addOn->log_debug($methodName . '(): Message - ' . $request->getMessage());
        $addOn->log_debug($methodName . '(): Data - ' . wp_json_encode($data));
        $addOn->log_debug($methodName . '(): TransactionId (VendorTxCode) - ' . $request->getTransactionId());
    }

    private static function getEntryByTransactionId(string $transactionId, GFPaymentAddOn $addOn): ?Entry
    {
        $rawEntry = self::getRawEntryByProperty($transactionId, $addOn);
        // Workaround for Gravity Forms Encrypted Fields.
        if (! is_array($rawEntry)) {
            $rawEntry = self::getRawEntryByMeta($transactionId, $addOn);
        }

        return is_array($rawEntry) ? new Entry($rawEntry) : null;
    }

    private static function getRawEntryByProperty(string $transactionId, GFPaymentAddOn $addOn): ?array
    {
        $entryId = $addOn->get_entry_by_transaction_id($transactionId);
        if (empty($entryId)) {
            return null;
        }

        $rawEntry = GFAPI::get_entry($entryId);
        return is_array($rawEntry) ? $rawEntry : null;
    }

    private static function getRawEntryByMeta(string $transactionId, GFPaymentAddOn $addOn): ?array
    {
        /** @var wpdb $wpdb */
        $wpdb = $GLOBALS['wpdb'];
        $entryMetaTableName = $addOn->get_entry_meta_table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $entryId = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT `entry_id` FROM %i WHERE `meta_key` = %s AND `meta_value` LIKE %s LIMIT 1',
                $entryMetaTableName,
                'transaction_reference',
                '%' . $wpdb->esc_like('"VendorTxCode":"' . $transactionId . '"') . '%',
            ),
        );

        if (empty($entryId)) {
            return null;
        }

        $rawEntry = GFAPI::get_entry($entryId);
        return is_array($rawEntry) ? $rawEntry : null;
    }

    private static function getFallbackNextUrl(): string
    {
        return home_url();
    }

    private static function getNextUrl(Entry $entry): string
    {
        return ConfirmationHandler::buildUrlFor($entry);
    }
}
