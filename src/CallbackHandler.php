<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFAPI;
use GFPaymentAddOn;
use Omnipay\SagePay\Message\ServerNotifyRequest;
use Omnipay\SagePay\ServerGateway;
use WP_Error;

class CallbackHandler
{
    public static function run(GFPaymentAddOn $addOn): void
    {
        $gateway = static::buildGatewayBySuperglobals($addOn);

        $addOn->log_debug(__METHOD__ . '(): Before accepting notification');

        /* @var ServerNotifyRequest $request */ // phpcs:ignore
        $request = $gateway->acceptNotification();

        static::logDebug(__METHOD__, $request, $addOn);

        $entry = static::getEntryByRequest($request, $addOn);

        $request->setTransactionReference(
            $entry->getMeta('transaction_reference')
        );

        // Get the response message ready for returning.
        /* @var ServerNotifyRequest $response */ // phpcs:ignore
        $response = $request->send();

        $addOn->log_debug(__METHOD__ . '(): After accepting notification');
        static::logDebug(__METHOD__, $response, $addOn);

        // Save the final transactionReference against the transaction in the database. It will
        // be needed if you want to capture the payment (for an authorize) or void or refund or
        // repeat the payment later.
        $entry->setMeta(
            'final_transaction_reference',
            $response->getTransactionReference()
        );

        $addOn->log_debug(__METHOD__ . '(): Final transaction reference saved');

        if (! $request->isValid()) {
            $message = 'Signature not valid';

            $addOn->log_error(__METHOD__ . '(): ' . $message);
            $entry->markAsFailed($addOn, $message);
            $response->invalid(
                static::getNextUrl($entry),
                $message
            );
        }

        switch ($request->getTransactionStatus()) {
            case $request::STATUS_COMPLETED:
                $entry->markAsPaid($addOn);
                break;
            case $request::STATUS_PENDING:
                $entry->markAsPending($addOn);
                break;
            default:
                $entry->markAsFailed($addOn);
                break;
        }

        $addOn->log_debug(__METHOD__ . '(): ' . static::getNextUrl($entry));
        $addOn->log_debug(__METHOD__ . '(): Confirm!');
        $response->confirm(
            static::getNextUrl($entry)
        );
        exit;
    }

    protected static function buildGatewayBySuperglobals(GFPaymentAddOn $addOn): ServerGateway
    {
        $vendor = rgget('vendor');
        $isTest = rgget('isTest');

        $addOn->log_debug(__METHOD__ . '(): Vendor - ' . $vendor . ' isTest - ' . $isTest);

        if ('' === $vendor || '' === $isTest) {
            $message = 'Unable to get vendor code and/or environment from superglobals';
            $addOn->log_error(__METHOD__ . '(): ' . $message);
            wp_die(esc_html($message), 'Bad Request', 400);
        }

        return GatewayFactory::build($vendor, (bool) $isTest);
    }

    /**
     * Log SagePay api object via Gravity Forms logger.
     *
     * @param string              $methodName The caller method name.
     * @param ServerNotifyRequest $request    SagePay api object.
     * @param GFPaymentAddOn      $addOn      Add-on instance.
     */
    protected static function logDebug(string $methodName, ServerNotifyRequest $request, GFPaymentAddOn $addOn): void
    {
        $addOn->log_debug($methodName . '(): Status - ' . $request->getTransactionStatus());
        $addOn->log_debug($methodName . '(): Message - ' . $request->getMessage());
        $addOn->log_debug($methodName . '(): Data - ' . wp_json_encode($request->getData()));
        $addOn->log_debug($methodName . '(): TransactionId (VendorTxCode) - ' . $request->getTransactionId());
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $addOn->log_debug($methodName . '(): TransactionReference (final_transaction_reference) - ' . wp_json_encode($request->getTransactionReference()));
    }

    protected static function getEntryByRequest(ServerNotifyRequest $request, GFPaymentAddOn $addOn): Entry
    {
        $rawEntry = static::getRawEntryByProperty($request, $addOn);
        // Workaround for Gravity Forms Encrypted Fields.
        if (empty($rawEntry)) {
            $rawEntry = static::getRawEntryByMeta($request, $addOn);
        }

        if (is_wp_error($rawEntry)) {
            /* @var WP_Error $rawEntry */ // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
            $message = __METHOD__ . '(): Unable to find entry by transaction id (VendorTxCode)' . $rawEntry->get_error_message(); // phpcs:ignore Generic.Files.LineLength.TooLong
            $addOn->log_error($message);

            /* @var ServerNotifyRequest $response */ // phpcs:ignore
            $response = $request->send();
            $response->error(
                static::getFallbackNextUrl(),
                $message
            );
            exit;
        }

        return new Entry($rawEntry);
    }

    protected static function getRawEntryByProperty(ServerNotifyRequest $request, GFPaymentAddOn $addOn): ?array
    {
        $entryId = $addOn->get_entry_by_transaction_id(
            $request->getTransactionId()
        );

        $rawEntry = GFAPI::get_entry($entryId);
        return is_array($rawEntry) ? $rawEntry : null;
    }

    protected static function getRawEntryByMeta(ServerNotifyRequest $request, GFPaymentAddOn $addOn): ?array
    {
        $wpdb = $GLOBALS['wpdb'];
        $entryMetaTableName = $addOn->get_entry_meta_table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            $wpdb->prepare(
            // See:  https://github.com/WordPress/WordPress-Coding-Standards/issues/1589.
            // phpcs:ignore Generic.Files.LineLength.TooLong,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT `entry_id` FROM `$entryMetaTableName` WHERE `meta_key` = 'transaction_reference' AND `meta_value` LIKE %s LIMIT 1",
                '%"VendorTxCode":"' . $request->getTransactionId() . '"%'
            ),
            ARRAY_A
        );

        if (! is_array($results)) {
            return null;
        }

        $firstResult = $results[0] ?? [];
        $entryId = $firstResult['entry_id'];

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
