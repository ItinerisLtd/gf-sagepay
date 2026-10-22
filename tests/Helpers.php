<?php

declare(strict_types=1);

namespace Itineris\SagePay\Tests;

use ArrayObject;
use GFAPI;
use GFFormDisplay;
use Itineris\SagePay\ConfirmationHandler;
use Itineris\SagePay\Entry;
use Mantle\Testing\WP_Die;
use RuntimeException;
use WP_Error;

use function array_merge;
use function is_array;
use function parse_url;

/**
 * Creates a form with a SagePay feed and an entry processed by that feed.
 *
 * @param array<string, mixed> $entry    Entry properties to override.
 * @param array<string, mixed> $feedMeta Feed meta to override.
 * @param array<string, mixed> $form     Form properties to override.
 */
function createSagePayEntry(array $entry = [], array $feedMeta = [], array $form = []): Entry
{
    $formId = GFAPI::add_form(array_merge(['title' => 'SagePay test form', 'fields' => []], $form));
    if ($formId instanceof WP_Error) {
        throw new RuntimeException($formId->get_error_message());
    }

    $feedId = GFAPI::add_feed(
        $formId,
        array_merge(['feedName' => 'SagePay', 'vendor' => 'testvendor', 'isTest' => 'test'], $feedMeta),
        'gf-sagepay',
    );
    if ($feedId instanceof WP_Error) {
        throw new RuntimeException($feedId->get_error_message());
    }

    $entryId = GFAPI::add_entry(array_merge([
        'form_id' => $formId,
        'source_url' => home_url('/payment-form/'),
        'payment_status' => 'Paid',
    ], $entry));
    if ($entryId instanceof WP_Error) {
        throw new RuntimeException($entryId->get_error_message());
    }

    gform_update_meta($entryId, 'processed_feeds', ['gf-sagepay' => [$feedId]]);

    $rawEntry = GFAPI::get_entry($entryId);
    if (! is_array($rawEntry)) {
        throw new RuntimeException('Unable to load the test entry.');
    }

    return new Entry($rawEntry);
}

/**
 * Issues a return URL for the entry and gives back its path and query, ready to request on the test site.
 */
function confirmationPath(Entry $entry, int $ttlInSeconds = 3600): string
{
    $url = ConfirmationHandler::buildUrlFor($entry, $ttlInSeconds);

    return parse_url($url, PHP_URL_PATH) . '?' . parse_url($url, PHP_URL_QUERY);
}

function confirmationShownFor(Entry $entry): bool
{
    return (bool) (GFFormDisplay::$submission[$entry->getFormId()]['is_confirmation'] ?? false);
}

/**
 * Reads entry meta from the database rather than Gravity Forms' per-process meta cache.
 */
function freshEntryMeta(Entry $entry, string $key): string
{
    $GLOBALS['_gform_lead_meta'] = [];

    return (string) gform_get_meta($entry->getId(), $key);
}

/**
 * Records the HTTP response code wp_die() would send, then throws Mantle's WP_Die_Exception as usual.
 *
 * @return ArrayObject<string, int|null>
 */
function captureWpDieResponseCode(): ArrayObject
{
    $capture = new ArrayObject(['response' => null]);

    add_filter('wp_die_handler', static fn () => static function ($message, $title = '', $args = []) use ($capture): void {
        [, , $parsedArgs] = _wp_die_process_input($message, $title, $args);
        $capture['response'] = $parsedArgs['response'];
        WP_Die::handler($message);
    }, 20);

    return $capture;
}
