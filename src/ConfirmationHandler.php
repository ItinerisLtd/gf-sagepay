<?php

declare(strict_types=1);

namespace Itineris\SagePay;

use GFAPI;
use GFCommon;
use GFFormDisplay;
use GFFormsModel;

/**
 * Taken from `GFPayPal::maybe_thankyou_page`.
 */
class ConfirmationHandler
{
    private const HASH_HMAC_ALGO = 'sha512';

    public static function init(): void
    {
        add_action('wp', [self::class, 'maybeThankYouPage'], 5);
    }

    public static function maybeThankYouPage(): void
    {
        $entryId = (int) rgget('entry');
        $token = rgget('gf-sagepay-token');

        if (empty($entryId) || empty($token)) {
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

        // Token validation passed. Make it invalid after first use.
        $entry->setConfirmationTokenExpiredAt(0);

        $form = GFAPI::get_form(
            $entry->getFormId()
        );

        if (! class_exists('GFFormDisplay')) {
            require_once GFCommon::get_base_path() . '/form_display.php';
        }

        $confirmation = GFFormDisplay::handle_confirmation($form, $entry->toArray(), false);

        if (is_array($confirmation) && isset($confirmation['redirect'])) {
            wp_redirect($confirmation['redirect']); // phpcs:ignore
            exit;
        }

        GFFormDisplay::$submission[$entry->getFormId()] = [
            'is_confirmation' => true,
            'confirmation_message' => $confirmation,
            'form' => $form,
            'lead' => $entry->toArray(),
        ];
    }

    private static function hash(string $confirmationToken): string
    {
        return hash_hmac(
            self::HASH_HMAC_ALGO,
            $confirmationToken,
            wp_salt('auth')
        );
    }

    public static function buildUrlFor(Entry $entry): string
    {
        $confirmationToken = GFFormsModel::get_uuid('-');

        $entry->setConfirmationTokenHash(
            self::hash($confirmationToken)
        );

        $entry->setConfirmationTokenExpiredAt(
            (int) apply_filters(
                'gf_sagepay_confirmation_token_expired_at',
                time() + 3600, // One hour later.
                $entry
            )
        );

        return esc_url_raw(
            add_query_arg(
                [
                    'entry' => $entry->getId(),
                    'gf-sagepay-token' => $confirmationToken,
                ],
                $entry->getProperty('source_url')
            )
        );
    }
}
