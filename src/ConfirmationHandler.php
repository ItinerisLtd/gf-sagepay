<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFAPI;
use GFCommon;
use GFFormDisplay;
use GFFormsModel;

use function class_exists;
use function ctype_digit;
use function hash_equals;
use function hash_hmac;
use function is_array;
use function is_int;
use function is_string;
use function time;

/**
 * Taken from `GFPayPal::maybe_thankyou_page`.
 */
class ConfirmationHandler
{
    private const HASH_HMAC_ALGO = 'sha512';

    public static function init(): void
    {
        add_action('wp', [self::class, 'maybeContinue'], 5);
    }

    public static function maybeContinue(): void
    {
        $entryId = rgget('entry');
        $token = rgget('gf-sagepay-token');

        if (! is_string($entryId) || ! ctype_digit($entryId) || ! is_string($token) || '' === $token) {
            return;
        }

        $entryId = (int) $entryId;
        if ($entryId <= 0) {
            return;
        }

        $rawEntry = GFAPI::get_entry($entryId);
        if (is_wp_error($rawEntry)) {
            return;
        }
        $entry = new Entry($rawEntry);

        if (time() > $entry->getConfirmationTokenExpiredAt()) {
            return;
        }

        $correctHash = $entry->getConfirmationTokenHash();
        $hash = self::hash($token);
        if (! hash_equals($correctHash, $hash)) {
            return;
        }

        $addOn = AddOn::get_instance();
        $isFailedPayment = ! $entry->isPaidOrPending() || $entry->isTimeout();
        $cancelUrl = $isFailedPayment ? self::getCancelUrl($addOn, $entry) : '';

        if ('' !== $cancelUrl) {
            self::consumeToken($addOn, $entry);
            // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
            wp_redirect($cancelUrl);
            exit;
        }

        // Successful payments, and failed payments without a cancel URL, show the normal Gravity Forms confirmation.
        $form = self::getForm($addOn, $entry);
        self::consumeToken($addOn, $entry);
        self::handle($entry, $form);
    }

    /**
     * Consumes the token only after everything the response needs has loaded, so a missing feed or form
     * leaves the token valid for a retry.
     */
    private static function consumeToken(AddOn $addOn, Entry $entry): void
    {
        $deleted = $entry->deleteConfirmationTokenHash();

        if (is_int($deleted) && $deleted > 0) {
            return;
        }

        if (0 === $deleted) {
            wp_die('This confirmation link has already been used.', '', ['response' => 200]);
        }

        // Database error, or Gravity Forms is blocking submissions during an upgrade. The token is still valid.
        $addOn->log_error(__METHOD__ . '(): Unable to delete confirmation token hash for entry ' . $entry->getId());
        wp_die('Unable to confirm your payment right now. Please try again shortly.', '', ['response' => 503]);
    }

    private static function hash(string $confirmationToken): string
    {
        return hash_hmac(
            self::HASH_HMAC_ALGO,
            $confirmationToken,
            wp_salt('auth'),
        );
    }

    private static function getCancelUrl(AddOn $addOn, Entry $entry): string
    {
        $rawFeed = $addOn->get_payment_feed($entry->toArray());

        if (empty($rawFeed)) {
            wp_die('Unable to locate feed');
        }

        return (new Feed($rawFeed))->getCancelUrl();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    private static function getForm(AddOn $addOn, Entry $entry)
    {
        $form = GFAPI::get_form(
            $entry->getFormId(),
        );

        if (! is_array($form)) {
            $addOn->log_error(
                __METHOD__ . '(): Unable to locate form ' . $entry->getFormId() . ' for entry ' . $entry->getId(),
            );
            wp_die('Unable to locate form');
        }

        return $form;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint $form
     */
    private static function handle(Entry $entry, $form): void
    {
        if (! class_exists('GFFormDisplay')) {
            require_once GFCommon::get_base_path() . '/form_display.php';
        }

        $confirmation = GFFormDisplay::handle_confirmation($form, $entry->toArray(), false);

        if (is_array($confirmation) && isset($confirmation['redirect'])) {
            // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
            wp_redirect($confirmation['redirect']);
            exit;
        }

        GFFormDisplay::$submission[$entry->getFormId()] = [
            'is_confirmation' => true,
            'confirmation_message' => $confirmation,
            'form' => $form,
            'lead' => $entry->toArray(),
        ];
    }

    public static function buildUrlFor(Entry $entry, int $ttlInSeconds = 3600): string
    {
        $confirmationToken = GFFormsModel::get_uuid('-');

        $entry->setConfirmationTokenHash(
            self::hash($confirmationToken),
            time() + $ttlInSeconds,
        );

        return esc_url_raw(
            add_query_arg(
                [
                    'entry' => $entry->getId(),
                    'gf-sagepay-token' => $confirmationToken,
                    'extra' => 'protection',
                ],
                $entry->getProperty('source_url'),
            ),
        );
    }
}
