<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GF_Fields;
use GFAPI;
use GFPaymentAddOn;
use Omnipay\Common\CreditCard;
use Omnipay\Omnipay;
use Omnipay\SagePay\Message\ServerAuthorizeResponse;
use Omnipay\SagePay\Message\ServerNotifyRequest;
use Omnipay\SagePay\Message\ServerNotifyResponse;
use Omnipay\SagePay\Message\ServerPurchaseRequest;
use Omnipay\SagePay\ServerGateway;
use Ramsey\Uuid\Uuid;

/**
 * Avoid adding code in this class!
 *
 * @see https://docs.gravityforms.com/gfpaymentaddon/
 */
class AddOn extends GFPaymentAddOn
{
    protected $_version = GFSagePay::VERSION;
    protected $_min_gravityforms_version = MinimumRequirements::GRAVITY_FORMS_VERSION;
    protected $_slug = 'gf-sagepay';
    protected $_path = 'gf-sagepay/src/AddOn.php';
    protected $_full_path = __FILE__;
    protected $_title = 'GF SagePay';
    protected $_short_title = 'GF SagePay';
    protected $_url = 'https://github.com/ItinerisLtd/gf-sagepay';
    protected $_supports_callbacks = true;

    /**
     * @var self|null $_instance If available, contains an instance of this class.
     */
    private static $_instance = null;

    /**
     * Returns an instance of this class, and stores it in the $_instance property.
     *
     * @return self $_instance An instance of this class.
     */
    public static function get_instance(): self
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function minimum_requirements(): array
    {
        return MinimumRequirements::toArray();
    }

    public function feed_settings_fields(): array
    {
        return FeedSettingsFields::toArray($this);
    }

    public function callback()
    {
        $entryId = rgget('entry_id');
        $entry = GFAPI::get_entry($entryId);

        if (is_wp_error($entry)) {
            wp_die('Entry not found', 'Bad Request', ['response' => 400]);
        }

        $feed = $this->get_payment_feed($entry);

        if (false === $feed) {
            wp_die('Feed not found', 'Bad Request', ['response' => 400]);
        }

        // TODO: Check feed active
        // TODO: Check entry exisits

        /** @var ServerGateway $gateway */
        $gateway = Omnipay::create('SagePay\Server');
        $gateway->setVendor($feed['meta']['vendor']);
        $gateway->setTestMode($feed['meta']['isTest']);

        /** @var ServerNotifyRequest $request */
        $request = $gateway->acceptNotification();

        $this->add_note($entry['id'], 'Status: ' . $request->getTransactionStatus());
        $this->add_note($entry['id'], 'Message: ' . $request->getMessage());
        $this->add_note($entry['id'], 'Data: ' . serialize($request->getData()));


        $request->setTransactionReference(
            gform_get_meta($entry['id'], 'transaction_reference')
        );

        // Get the response message ready for returning.
        /** @var ServerNotifyResponse $response */
        $response = $request->send();

        $nextUrl = $feed['meta']['nextUrl'];
        $this->add_note($entry['id'], 'Next URL: ' . $nextUrl);

        // Save the final transactionReference against the transaction in the database. It will
        // be needed if you want to capture the payment (for an authorize) or void or refund or
        // repeat the payment later.
        gform_update_meta($entry['id'], 'final_transaction_reference',
            $response->getTransactionReference()
        );

        if (! $request->isValid()) {
            // Respond to Sage Pay indicating we are not accepting anything about this message.
            $response->invalid($nextUrl, 'Signature not valid - goodbye');
        }

        switch ($request->getTransactionStatus()) {
            case $request::STATUS_COMPLETED:
                $type = 'complete_payment';
                break;
            case $request::STATUS_PENDING;
                $type = 'add_pending_payment';
                break;
            default:
                $type = 'fail_payment';
                break;
        }

        $this->processCallbackAction($entry, [
            'type' => $type,
            'amount' => rgar($entry, 'payment_amount'),
            'entry_id' => $entry['id'],
            'transaction_id' => rgar($entry, 'transaction_id'),
            'transaction_type' => false,
            'subscription_id' => false,
            'payment_status' => false,
            'note' => false,
        ]);

        // Now let Sage Pay know you have got it and saved the details away safely:
        $response->confirm($nextUrl);
    }

    private function processCallbackAction($entry, $action): void
    {
        $this->log_debug(__METHOD__ . '(): Processing callback action.');

        /**
         * Performs actions before the the payment action callback is processed.
         *
         * @since Unknown
         *
         * @param array $action The action array.
         * @param array $entry  The Entry Object.
         */
        do_action('gform_action_pre_payment_callback', $action, $entry);
        if (has_filter('gform_action_pre_payment_callback')) {
            $this->log_debug(__METHOD__ . '(): Executing functions hooked to gform_action_pre_payment_callback.');
        }

        $result = false;
        switch ($action['type']) {
            case 'complete_payment':
                $result = $this->complete_payment($entry, $action);
                break;
            case 'fail_payment':
                $result = $this->fail_payment($entry, $action);
                break;
            case 'add_pending_payment':
                $result = $this->add_pending_payment($entry, $action);
                break;
        }

        /**
         * Fires right after the payment callback.
         *
         * @since Unknown
         *
         * @param array $entry            The Entry Object
         * @param array $action           {
         *                                The action performed.
         *
         * @type string $type             The callback action type. Required.
         * @type string $transaction_id   The transaction ID to perform the action on. Required if the action is a payment.
         * @type string $subscription_id  The subscription ID. Required if this is related to a subscription.
         * @type string $amount           The transaction amount. Typically required.
         * @type int    $entry_id         The ID of the entry associated with the action. Typically required.
         * @type string $transaction_type The transaction type to process this action as. Optional.
         * @type string $payment_status   The payment status to set the payment to. Optional.
         * @type string $note             The note to associate with this payment action. Optional.
         * }
         *
         * @param mixed $result           The Result Object.
         */
        do_action('gform_post_payment_callback', $entry, $action, $result);
        if (has_filter('gform_post_payment_callback')) {
            $this->log_debug(__METHOD__ . '(): Executing functions hooked to gform_post_payment_callback.');
        }
    }

    /**
     * Specify a URL to SagePay.
     *
     * @param array $feed            Active payment feed containing all the configuration data.
     * @param array $submission_data Contains form field data submitted by the user as well as payment information
     *                               (i.e. payment amount, setup fee, line items, etc...).
     * @param array $form            Current form array containing all form settings.
     * @param array $entry           Current entry array containing entry information (i.e data submitted by users).
     *
     * @return string Return a full URL (including https://) to SagePay OR empty string.
     */
    public function redirect_url($feed, $submission_data, $form, $entry): string
    {
        // See: https://docs.gravityforms.com/entry-object/#pricing-properties
        GFAPI::update_entry_property($entry['id'], 'currency', rgar($entry, 'currency'));
        GFAPI::update_entry_property($entry['id'], 'payment_status', 'Processing');
        GFAPI::update_entry_property($entry['id'], 'is_fulfilled', 0);

        // Create a unique transaction ID to track this transaction.
        $uuid4 = Uuid::uuid4();
        $transactionUuid = $uuid4->toString();
        gform_update_meta($entry['id'], 'transaction_uuid', $transactionUuid);

        GFAPI::update_entry_property($entry['id'], 'payment_amount', rgar($submission_data, 'payment_amount'));

        /** @var ServerGateway $gateway */
        $gateway = Omnipay::create('SagePay\Server');

        $gateway->setVendor($feed['meta']['vendor']);
        $gateway->setTestMode($feed['meta']['isTest']);

        $billingCountry = GF_Fields::get('address')->get_country_code(rgar($entry,
            $feed['meta']['billingInformation_country']));
        $billingState = GF_Fields::get('address')->get_us_state_code(rgar($entry,
            $feed['meta']['billingInformation_state']));
        $shippingCountry = GF_Fields::get('address')->get_country_code(rgar($entry,
            $feed['meta']['shippingInformation_country']));
        $shippingState = GF_Fields::get('address')->get_us_state_code(rgar($entry,
            $feed['meta']['shippingInformation_state']));

        $creditCard = new CreditCard(
            array_filter([
                'firstName' => rgar($entry, $feed['meta']['customerInformation_firstName']),
                'lastName' => rgar($entry, $feed['meta']['customerInformation_lastName']),
                'email' => rgar($entry, $feed['meta']['customerInformation_email']),
                'billingPhone' => rgar($entry, $feed['meta']['customerInformation_phone']),

                'billingAddress1' => rgar($entry, $feed['meta']['billingInformation_address']),
                'billingAddress2' => rgar($entry, $feed['meta']['billingInformation_address2']),
                'billingCity' => rgar($entry, $feed['meta']['billingInformation_city']),
                'billingPostcode' => rgar($entry, $feed['meta']['billingInformation_zip']),
                'billingCountry' => $billingCountry,
                'billingState' => $billingState,

                'shippingAddress1' => rgar($entry, $feed['meta']['shippingInformation_address']),
                'shippingAddress2' => rgar($entry, $feed['meta']['shippingInformation_address2']),
                'shippingCity' => rgar($entry, $feed['meta']['shippingInformation_city']),
                'shippingPostcode' => rgar($entry, $feed['meta']['shippingInformation_zip']),
                'shippingCountry' => $shippingCountry,
                'shippingState' => $shippingState,
            ]));

        /** @var ServerPurchaseRequest $request */
        $request = $gateway->purchase([
            'amount' => rgar($submission_data, 'payment_amount'),
            'currency' => rgar($entry, 'currency'),
            'card' => $creditCard,
            'notifyUrl' => home_url('/?callback=' . $this->_slug . '&entry_id=' . $entry['id']),
            'transactionId' => $transactionUuid,
            'description' => $feed['meta']['description'],
        ]);

        $request->setApply3DSecure($feed['meta']['3dSecure']);
        $request->setApplyAVSCV2($feed['meta']['avscv2']);

        /** @var ServerAuthorizeResponse $response */
        $response = $request->send();

        // Note that at this point `transactionReference` is not yet complete for the Server transaction,
        // but must be saved in the database for the notification handler to use.
        GFAPI::update_entry_property($entry['id'], 'transaction_id', $response->getTransactionReference());

        if (! $response->isRedirect()) {
            // TODO: Stop the form!
            GFAPI::update_entry_property($entry['id'], 'payment_status', 'Failed');
            $this->add_feed_error(__METHOD__ . '(): Unable to forward user onto SagePay - ' . $response->getMessage(),
                $feed, $entry, $form);

            return '';
        }

        $this->log_debug(__METHOD__ . "(): Forward user onto SagePay's checkout form.");

        return $response->getRedirectUrl();
    }
}
