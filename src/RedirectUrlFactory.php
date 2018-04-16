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

        /* @var ServerPurchaseRequest $request SagePay server purchase request */
        $request = $gateway->purchase([
            'amount' => $entry->getMeta('payment_amount'),
            'currency' => $entry->getProperty('currency'),
            'card' => CreditCardFactory::build($feed, $entry),
            'notifyUrl' => home_url('/?callback=' . $addOn->get_slug()),
            'transactionId' => $entry->getMeta('transaction_uuid'),
            'description' => $feed->getMeta('description'),
        ]);

        $request->setApply3DSecure($feed->getMeta('3dSecure'));
        $request->setApplyAVSCV2($feed->getMeta('avscv2'));

        /* @var ServerAuthorizeResponse $response SagePay server authorize response */
        $response = $request->send();

        // Note that at this point `transactionReference` is not yet complete for the Server transaction,
        // but must be saved in the database for the notification handler to use.
        $entry->setProperty('transaction_id', $response->getTransactionReference());

        if (! $response->isRedirect()) {
            $note = __METHOD__ . '(): Unable to forward user onto SagePay - ' . $response->getMessage();
            $entry->markAsFailed($addOn, $note);

            return '';
        }

        $addOn->log_debug(__METHOD__ . '(): Forward user onto SagePay checkout form.');

        return $response->getRedirectUrl();
    }
}
