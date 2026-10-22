<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFAPI;
use GFPaymentAddOn;
use Omnipay\Common\Message\NotificationInterface;
use Omnipay\SagePay\Message\ServerNotifyRequest;
use Omnipay\SagePay\ServerGateway;
use Throwable;
use wpdb;

use function hash_equals;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;

class CallbackHandler
{
    public static function run(GFPaymentAddOn $addOn): void
    {
        $gateway = static::buildGatewayBySuperglobals($addOn);

        /** @var ServerNotifyRequest $request */
        $request = $gateway->acceptNotification();

        try {
            $entry = static::getVerifiedEntry($request, $addOn);
        } catch (Throwable $exception) {
            static::logException(__METHOD__, $exception, $addOn);
            static::reject($request, $addOn, 'Unable to process notification');
        }

        // The notification is genuine from here on, so SagePay always gets an OK reply.
        try {
            static::applyStatus($request, $entry, $addOn);
            $nextUrl = static::getNextUrl($entry);
        } catch (Throwable $exception) {
            static::logException(__METHOD__, $exception, $addOn);
            $nextUrl = static::getFallbackNextUrl();
        }

        $addOn->log_debug(__METHOD__ . '(): Confirm entry ' . $entry->getId());
        $request->confirm($nextUrl);
        exit;
    }

    /**
     * Rejects anything that cannot be matched to an entry and verified, leaving the entry untouched.
     */
    protected static function getVerifiedEntry(ServerNotifyRequest $request, GFPaymentAddOn $addOn): Entry
    {
        if (! static::hasOnlyStringFields($request)) {
            static::reject($request, $addOn, 'Malformed notification');
        }

        $transactionId = $request->getTransactionId();
        if (! is_string($transactionId) || ! wp_is_uuid($transactionId)) {
            static::reject($request, $addOn, 'Unknown transaction');
        }

        $entry = static::getEntryByTransactionId($transactionId, $addOn);
        if (! $entry instanceof Entry) {
            static::reject($request, $addOn, 'Unknown transaction');
        }

        $reference = $entry->getMeta('transaction_reference');
        if (! static::isUsableReference($reference, $transactionId)) {
            static::reject($request, $addOn, 'Unusable transaction reference for entry ' . $entry->getId());
        }

        $request->setTransactionReference($reference);

        if (! $request->isValid()) {
            static::reject($request, $addOn, 'Signature not valid for entry ' . $entry->getId());
        }

        static::logDebug(__METHOD__, $request, $addOn);

        return $entry;
    }

    protected static function reject(ServerNotifyRequest $request, GFPaymentAddOn $addOn, string $message): never
    {
        $addOn->log_error(__METHOD__ . '(): ' . $message);
        $request->invalid(
            static::getFallbackNextUrl(),
            $message,
        );
        exit;
    }

    protected static function buildGatewayBySuperglobals(GFPaymentAddOn $addOn): ServerGateway
    {
        $vendor = rgget('vendor');
        $isTest = rgget('isTest');

        if (! is_string($vendor) || '' === $vendor || ! in_array($isTest, ['true', 'false'], true)) {
            $message = 'Unable to get vendor code and/or environment from superglobals';
            $addOn->log_error(__METHOD__ . '(): ' . $message);
            wp_die(esc_html($message), 'Bad Request', 400);
            exit;
        }

        $addOn->log_debug(__METHOD__ . '(): Vendor - ' . $vendor . ' isTest - ' . $isTest);

        return GatewayFactory::build($vendor, 'true' === $isTest);
    }

    protected static function hasOnlyStringFields(ServerNotifyRequest $request): bool
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
    protected static function isUsableReference($reference, string $transactionId): bool
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
    protected static function applyStatus(ServerNotifyRequest $request, Entry $entry, GFPaymentAddOn $addOn): void
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

    protected static function logException(string $methodName, Throwable $exception, GFPaymentAddOn $addOn): void
    {
        $addOn->log_error(
            $methodName . '(): ' . $exception->getMessage()
            . ' in ' . $exception->getFile() . ':' . $exception->getLine(),
        );
    }

    /**
     * Log the notification via Gravity Forms logger, without the signature.
     */
    protected static function logDebug(string $methodName, ServerNotifyRequest $request, GFPaymentAddOn $addOn): void
    {
        $data = $request->getData();
        unset($data['VPSSignature']);

        $addOn->log_debug($methodName . '(): Status - ' . $request->getTransactionStatus());
        $addOn->log_debug($methodName . '(): Message - ' . $request->getMessage());
        $addOn->log_debug($methodName . '(): Data - ' . wp_json_encode($data));
        $addOn->log_debug($methodName . '(): TransactionId (VendorTxCode) - ' . $request->getTransactionId());
    }

    protected static function getEntryByTransactionId(string $transactionId, GFPaymentAddOn $addOn): ?Entry
    {
        $rawEntry = static::getRawEntryByProperty($transactionId, $addOn);
        // Workaround for Gravity Forms Encrypted Fields.
        if (! is_array($rawEntry)) {
            $rawEntry = static::getRawEntryByMeta($transactionId, $addOn);
        }

        return is_array($rawEntry) ? new Entry($rawEntry) : null;
    }

    protected static function getRawEntryByProperty(string $transactionId, GFPaymentAddOn $addOn): ?array
    {
        $entryId = $addOn->get_entry_by_transaction_id($transactionId);
        if (empty($entryId)) {
            return null;
        }

        $rawEntry = GFAPI::get_entry($entryId);
        return is_array($rawEntry) ? $rawEntry : null;
    }

    protected static function getRawEntryByMeta(string $transactionId, GFPaymentAddOn $addOn): ?array
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

    protected static function getFallbackNextUrl(): string
    {
        return home_url();
    }

    protected static function getNextUrl(Entry $entry): string
    {
        return ConfirmationHandler::buildUrlFor($entry);
    }
}
