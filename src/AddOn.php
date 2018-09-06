<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFPaymentAddOn;

/**
 * Avoid adding code in this class!
 *
 * @see https://docs.gravityforms.com/gfpaymentaddon/
 */
class AddOn
  extends GFPaymentAddOn
{
    private static $_instance = null;
    protected $_version = GFSagePay::VERSION;
    protected $_min_gravityforms_version = MinimumRequirements::GRAVITY_FORMS_VERSION;
    protected $_slug = 'gf-sagepay';
    protected $_path = 'gf-sagepay/gf-sagepay.php';
    protected $_full_path = __FILE__;
    protected $_title = 'GF SagePay';
    protected $_short_title = 'GF SagePay';
    protected $_url = 'https://github.com/ItinerisLtd/gf-sagepay';
    protected $_supports_callbacks = true;

    protected $_capabilities = ['gf_sagepay', 'gf_sagepay_uninstall'];
    protected $_capabilities_settings_page = 'gf_sagepay';
    protected $_capabilities_form_settings = 'gf_sagepay';
    protected $_capabilities_uninstall = 'gf_sagepay_uninstall';

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

    public function supported_notification_events($form): array
    {
        if (! $this->has_feed($form['id'])) {
            return [];
        }

        return SupportedNotificationEvents::toArray();
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
        return RedirectUrlFactory::build(
            $this,
            new Feed($feed),
            new Entry($entry),
            (float) rgar($submission_data, 'payment_amount')
        );
    }

    public function callback(): void
    {
        CallbackHandler::run($this);
    }
}
