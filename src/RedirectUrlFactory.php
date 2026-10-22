<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFFormsModel;
use GFPaymentAddOn;
use Omnipay\SagePay\Message\ServerAuthorizeResponse;
use Omnipay\SagePay\Message\ServerPurchaseRequest;
use Throwable;

/**
 * @internal
 */
final class RedirectUrlFactory
{
    public static function build(GFPaymentAddOn $addOn, Feed $feed, Entry $entry, float $amount): string
    {
        $transactionId = GFFormsModel::get_uuid('-');
        $addOn->log_debug(__METHOD__ . '():  Generated $transactionId - ' . $transactionId);

        $entry->markAsProcessing($transactionId, $amount);
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $addOn->log_debug(__METHOD__ . '():  Saved transactionId (' . $entry->getMeta('transaction_id') . ') as entry property on entry id: ' . $entry->getId() . ')');
        // Workaround for Gravity Forms Encrypted Fields.
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $addOn->log_debug(__METHOD__ . '():  Saved transactionId (' . $entry->getMeta('transaction_id') . ') as entry meta on entry id: ' . $entry->getId() . ')');

        // Notifications are verified against the vendor code the payment was registered with.
        $entry->setMeta('gf_sagepay_vendor', $feed->getVendor());
        $addOn->log_debug(
            __METHOD__ . '(): Registering with vendor - ' . $feed->getVendor()
            . ' isTest - ' . ($feed->isTest() ? 'true' : 'false'),
        );

        try {
            $response = self::register($addOn, $feed, $entry);
        } catch (Throwable $exception) {
            self::handleException($exception, $entry, $addOn);

            return '';
        }

        $addOn->log_debug(__METHOD__ . '():  ServerAuthorizeResponse - ' . $response->getMessage());

        // Note that at this point `transactionReference` is not yet complete for the Server transaction,
        // but must be saved in the database for the notification handler to use.
        $entry->setMeta('transaction_reference', $response->getTransactionReference());
        $addOn->log_debug(__METHOD__ . '(): Transaction reference saved');

        if (! $response->isRedirect()) {
            self::handleFailure($response, $entry, $addOn);

            return '';
        }

        $addOn->log_debug(__METHOD__ . '(): Forward user onto SagePay checkout form.');

        return $response->getRedirectUrl();
    }

    /**
     * Sends the transaction registration to SagePay.
     */
    private static function register(GFPaymentAddOn $addOn, Feed $feed, Entry $entry): ServerAuthorizeResponse
    {
        // Temporarily reset $_FILES to prevent conflicts with symfony/http-foundation.
        $originalFiles = $_FILES; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $_FILES = [];

        try {
            $gateway = GatewayFactory::buildFromFeed($feed);
        } finally {
            // Restore original $_FILES so that GravityForms saves it.
            $_FILES = $originalFiles;
        }

        $vendorData = $entry->getProperty(
            $feed->getMeta('other_vendorData'),
        );

        /** @var ServerPurchaseRequest $request */
        $request = $gateway->purchase([
            'amount' => $entry->getProperty('payment_amount'),
            'currency' => $entry->getProperty('currency'),
            'card' => CreditCardFactory::build($feed, $entry),
            'notifyUrl' => self::getNotifyUrl($addOn),
            'transactionId' => $entry->getProperty('transaction_id'),
            'description' => $feed->getMeta('description'),
            'apply3DSecure' => $feed->getMeta('3dSecure'),
            'applyAVSCV2' => $feed->getMeta('avscv2'),
            'allowGiftAid' => $feed->isAllowGiftAid(),
            'vendorData' => $vendorData,
        ]);

        /** @var ServerAuthorizeResponse $response */
        $response = $request->send();

        return $response;
    }

    private static function getNotifyUrl(GFPaymentAddOn $addOn): string
    {
        return esc_url_raw(
            add_query_arg(
                ['callback' => $addOn->get_slug()],
                home_url(),
            ),
        );
    }

    private static function handleFailure(ServerAuthorizeResponse $response, Entry $entry, GFPaymentAddOn $addOn): void // phpcs:ignore Generic.Files.LineLength.TooLong
    {
        self::fail(
            __METHOD__ . '(): Unable to retrieve SagePay redirect url - ' . $response->getMessage(),
            'gf_sagepay_redirect_url_failure_wp_die',
            $response,
            $entry,
            $addOn,
        );
    }

    /**
     * Handles a registration that never got a reply from SagePay, such as a network error or a request omnipay rejects.
     */
    private static function handleException(Throwable $exception, Entry $entry, GFPaymentAddOn $addOn): void
    {
        self::fail(
            __METHOD__ . '(): Unable to register the payment with SagePay - ' . $exception::class . ': '
            . $exception->getMessage(),
            'gf_sagepay_redirect_url_exception_wp_die',
            $exception,
            $entry,
            $addOn,
        );
    }

    /**
     * Marks the entry failed, then stops the request with an error unless the filter returns false.
     */
    private static function fail(
        string $note,
        string $filter,
        ServerAuthorizeResponse|Throwable $reason,
        Entry $entry,
        GFPaymentAddOn $addOn
    ): void {
        $entry->markAsFailed($addOn, $note);

        $shouldWpDie = (bool) apply_filters($filter, true, $reason, $entry, $addOn);

        if (! $shouldWpDie) {
            return;
        }

        wp_die(
            esc_html__(
                'Error: Failed to retrieve SagePay checkout form URL. Please contact site administrators.',
                'gf-sagepay',
            ),
        );
    }
}
