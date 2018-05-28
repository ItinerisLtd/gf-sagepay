<?php
declare(strict_types=1);

namespace Itineris\SagePay;

use GFAPI;

class AddressCopier
{
    private const SUBFIELD_KEYS = ['address', 'address2', 'city', 'zip', 'state', 'country'];

    public static function init(): void
    {
        add_action('gform_pre_validation', [self::class, 'maybeCopyBillingAddressToShippingAddress']);
    }

    public static function maybeCopyBillingAddressToShippingAddress(array $form): array
    {
        $feed = self::getFeed((int) $form['id']);
        if (null === $feed || ! self::shouldShipToBillingAddress($feed)) {
            return $form;
        }

        array_map(function (string $key) use ($feed): void {
            self::copySubfield($feed, $key);
        }, self::SUBFIELD_KEYS);

        return $form;
    }

    private static function getFeed(int $formId): ?Feed
    {
        $addOn = AddOn::get_instance();

        $feeds = GFAPI::get_feeds(
            null,
            $formId,
            $addOn->get_slug(),
            true
        );

        if (! is_array($feeds) || empty($feeds)) {
            return null;
        }

        return new Feed(
            $feeds[0]
        );
    }

    private static function shouldShipToBillingAddress(Feed $feed): bool
    {
        $id = self::getInputId($feed, 'shippingInformation_shipToBillingAddress');

        return null !== $id && 'true' === rgpost($id);
    }

    private static function getInputId(Feed $feed, string $key): ?string
    {
        $id = $feed->getMeta($key);

        if (empty($id)) {
            return null;
        }

        return 'input_' . str_replace('.', '_', $id);
    }

    public static function copySubfield(Feed $feed, string $key): void
    {
        $billingInputId = self::getInputId($feed, 'billingInformation_' . $key);
        $shippingIdInputId = self::getInputId($feed, 'shippingInformation_' . $key);

        if (null === $billingInputId || null === $shippingIdInputId) {
            return;
        }

        $_POST[$shippingIdInputId] = rgpost($billingInputId);
    }
}
