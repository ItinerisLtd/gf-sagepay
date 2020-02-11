<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFFormsModel;
use GFPaymentAddOn;
use Omnipay\SagePay\Message\ServerAuthorizeResponse;
use Omnipay\SagePay\Message\ServerPurchaseRequest;

class RedirectUrlFactory
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

        // Temporarily reset $_FILES to prevent conflicts with symfony/http-foundation.
        $originalFiles = $_FILES; // phpcs:ignore
        $_FILES = [];

        $gateway = GatewayFactory::buildFromFeed($feed);

        // Restore original $_FILES so that GravityForms saves it.
        $_FILES = $originalFiles;

        $vendorData = $entry->getProperty(
            $feed->getMeta('other_vendorData')
        );

        /* @var ServerPurchaseRequest $request */ // phpcs:ignore
        $request = $gateway->purchase([
            'amount' => $entry->getProperty('payment_amount'),
            'currency' => $entry->getProperty('currency'),
            'card' => CreditCardFactory::build($feed, $entry),
            'notifyUrl' => self::getNotifyUrl($addOn, $feed),
            'transactionId' => $entry->getProperty('transaction_id'),
            'description' => $feed->getMeta('description'),
            'apply3DSecure' => $feed->getMeta('3dSecure'),
            'applyAVSCV2' => $feed->getMeta('avscv2'),
            'allowGiftAid' => $feed->isAllowGiftAid(),
            'vendorData' => $vendorData,
        ]);

        /* @var ServerAuthorizeResponse $response */ // phpcs:ignore
        $response = $request->send();
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

    protected static function getNotifyUrl(GFPaymentAddOn $addOn, Feed $feed): string
    {
        $callback = $addOn->get_slug();
        $vendor = $feed->getVendor();
        $isTest = $feed->isTest() ? 'true' : 'false';

        $addOn->log_debug(__METHOD__ . '(): Callback - ' . $callback . ' Vendor - ' . $vendor . ' isTest - ' . $isTest);

        return esc_url_raw(
            add_query_arg(
                [
                    'callback' => $callback,
                    'vendor' => $vendor,
                    'isTest' => $isTest,
                ],
                home_url()
            )
        );
    }

    protected static function handleFailure(ServerAuthorizeResponse $response, Entry $entry, GFPaymentAddOn $addOn): void // phpcs:ignore Generic.Files.LineLength.TooLong
    {
        $entry->markAsFailed(
            $addOn,
            __METHOD__ . '(): Unable to retrieve SagePay redirect url - ' . $response->getMessage()
        );

        $shouldWpDie = (bool) apply_filters('gf_sagepay_redirect_url_failure_wp_die', true, $response, $entry, $addOn);

        if (! $shouldWpDie) {
            return;
        }

        wp_die(
            esc_html__(
                'Error: Failed to retrieve SagePay checkout form URL. Please contact site administrators.',
                'gf-sagepay'
            )
        );
    }
}
