<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFPaymentAddOn;
use Omnipay\SagePay\Message\ServerAuthorizeResponse;
use Omnipay\SagePay\Message\ServerPurchaseRequest;
use Ramsey\Uuid\Uuid;

class RedirectUrlFactory
{
    public static function build(GFPaymentAddOn $addOn, Feed $feed, Entry $entry, float $amount): string
    {
        $entry->makeAsProcessing(
            Uuid::uuid4()->toString(),
            $amount
        );

        $gateway = GatewayFactory::buildFromFeed($feed);

        /* @var ServerPurchaseRequest $request */ // phpcs:ignore
        $request = $gateway->purchase([
            'amount' => $entry->getProperty('payment_amount'),
            'currency' => $entry->getProperty('currency'),
            'card' => CreditCardFactory::build($feed, $entry),
            'notifyUrl' => home_url('/?callback=' . $addOn->get_slug() . '&entry=' . $entry->getId()),
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
            $note = __METHOD__ . '(): Unable to forward user onto SagePay - ' . $response->getMessage();
            $entry->markAsFailed($addOn, $note);

            return '';
        }

        $addOn->log_debug(__METHOD__ . '(): Forward user onto SagePay checkout form.');

        return $response->getRedirectUrl();
    }
}
