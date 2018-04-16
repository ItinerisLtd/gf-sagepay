<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFPaymentAddOn;
use Omnipay\SagePay\Message\ServerNotifyRequest;
use Omnipay\SagePay\Message\ServerNotifyResponse;

class NotificationHandler
{
    public static function run(GFPaymentAddOn $addOn): void
    {
        $temporaryGateway = GatewayFactory::create();
        /* @var ServerNotifyRequest $temporaryRequest Temporary until we find out the vendor code and environment */
        $temporaryRequest = $temporaryGateway->acceptNotification();

        $rawEntry = $addOn->get_entry_by_transaction_id(
            $temporaryRequest->getTransactionId()
        );
        if (empty($rawEntry)) {
            wp_die('Unable to find entry, vendor code or environment', 'Bad Request', 400);
        }
        $entry = new Entry($rawEntry);

        $rawFeed = $addOn->get_payment_feed($entry);
        if (empty($rawFeed)) {
            wp_die('Unable to find entry, vendor code or environment', 'Bad Request', 400);
        }
        $feed = new Feed($rawFeed);

        $gateway = GatewayFactory::buildFromFeed($feed);

        /* @var ServerNotifyRequest $request */
        $request = $gateway->acceptNotification();

        $addOn->log_debug(__METHOD__ . '(): Status - ' . $request->getTransactionStatus());
        $addOn->log_debug(__METHOD__ . '(): Message - ' . $request->getMessage());
        $addOn->log_debug(__METHOD__ . '(): Data - ' . wp_json_encode($request->getData()));

        $request->setTransactionReference(
            $entry->getProperty('transaction_id')
        );

        // Get the response message ready for returning.
        /* @var ServerNotifyResponse $response */
        $response = $request->send();

        $addOn->log_debug(__METHOD__ . '(): Status - ' . $response->getTransactionStatus());
        $addOn->log_debug(__METHOD__ . '(): Message - ' . $response->getMessage());
        $addOn->log_debug(__METHOD__ . '(): Data - ' . wp_json_encode($response->getData()));

        $nextUrl = $feed->getMeta('nextUrl');

        // Save the final transactionReference against the transaction in the database. It will
        // be needed if you want to capture the payment (for an authorize) or void or refund or
        // repeat the payment later.
        $entry->setMeta(
            'final_transaction_reference',
            $response->getTransactionReference()
        );

        if (! $request->isValid()) {
            $entry->markAsFailed($addOn, 'Signature not valid');
            $response->invalid($nextUrl, 'Signature not valid');
        }

        if (! $feed->isActive()) {
            $entry->markAsFailed($addOn, 'Feed inactive');
            $response->invalid($nextUrl, 'Feed inactive');
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

        $response->confirm($nextUrl);
    }
}
