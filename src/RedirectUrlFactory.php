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
        $entry->makeAsProcessing(
            GFFormsModel::get_uuid('-'),
            $amount
        );

        $gateway = GatewayFactory::buildFromFeed($feed);

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
        ]);

        /* @var ServerAuthorizeResponse $response */ // phpcs:ignore
        $response = $request->send();
        $addOn->log_debug(__METHOD__ . '():  ServerAuthorizeResponse - ' . $response->getMessage());

        // Note that at this point `transactionReference` is not yet complete for the Server transaction,
        // but must be saved in the database for the notification handler to use.
        $entry->setMeta('transaction_reference', $response->getTransactionReference());
        $addOn->log_debug(__METHOD__ . '(): Set transaction reference to ' . $entry->getMeta('transaction_reference'));

        if (! $response->isRedirect()) {
            self::handleFailure($response, $entry, $addOn);

            return '';
        }

        $addOn->log_debug(__METHOD__ . '(): Forward user onto SagePay checkout form.');

        return $response->getRedirectUrl();
    }

    private static function getNotifyUrl(GFPaymentAddOn $addOn, Feed $feed): string
    {
        return esc_url_raw(
            add_query_arg(
                [
                    'callback' => $addOn->get_slug(),
                    'vendor' => $feed->getVendor(),
                    'isTest' => $feed->isTest() ? 'true' : 'false',
                ],
                home_url()
            )
        );
    }

    private static function handleFailure(ServerAuthorizeResponse $response, Entry $entry, GFPaymentAddOn $addOn): void
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
