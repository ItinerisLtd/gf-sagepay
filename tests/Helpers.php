<?php

declare(strict_types=1);

namespace Itineris\SagePay\Tests;

use ArrayObject;
use GFAPI;
use GFFormDisplay;
use Itineris\SagePay\ConfirmationHandler;
use Itineris\SagePay\Entry;
use Mantle\Testing\Test_Response;
use Mantle\Testing\WP_Die;
use Omnipay\Common\Http\Client;
use Omnipay\SagePay\Message\ServerNotifyRequest;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use WP_Error;

use function array_merge;
use function is_array;
use function json_encode;
use function parse_url;
use function preg_quote;
use function strtoupper;

const TEST_VENDOR = 'testvendor';
const TEST_SECURITY_KEY = 'SECURITYKEY';
const TEST_VPS_TX_ID = '{11111111-2222-3333-4444-555555555555}';

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
        array_merge(['feedName' => 'SagePay', 'vendor' => TEST_VENDOR, 'isTest' => 'test'], $feedMeta),
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

/**
 * Creates an entry waiting for SagePay's notification, as RedirectUrlFactory leaves it after registration.
 *
 * @param array<string, mixed> $entry Entry properties to override.
 */
function createPendingSagePayEntry(string $securityKey = TEST_SECURITY_KEY, array $entry = []): Entry
{
    $transactionId = wp_generate_uuid4();
    $sagePayEntry = createSagePayEntry(array_merge([
        'transaction_id' => $transactionId,
        'payment_status' => 'Processing',
        'payment_amount' => '10.00',
    ], $entry));

    gform_update_meta($sagePayEntry->getId(), 'transaction_id', $transactionId);
    gform_update_meta($sagePayEntry->getId(), 'payment_amount', '10.00');
    gform_update_meta($sagePayEntry->getId(), 'transaction_reference', json_encode([
        'SecurityKey' => $securityKey,
        'VPSTxId' => TEST_VPS_TX_ID,
        'VendorTxCode' => $transactionId,
    ]));

    return $sagePayEntry;
}

/**
 * Builds SagePay notification POST fields, signed the way SagePay signs them.
 *
 * @param array<string, string> $fields Fields to override before signing.
 *
 * @return array<string, string>
 */
function signedNotification(string $transactionId, string $securityKey = TEST_SECURITY_KEY, array $fields = []): array
{
    $fields = array_merge([
        'VPSProtocol' => '4.00',
        'TxType' => 'PAYMENT',
        'VendorTxCode' => $transactionId,
        'VPSTxId' => TEST_VPS_TX_ID,
        'Status' => 'OK',
        'StatusDetail' => '0000 : The Authorisation was Successful.',
        'TxAuthNo' => '4242',
    ], $fields);

    $signer = new ServerNotifyRequest(new Client(), Request::create('/', 'POST', $fields));
    $signer->setVendor(TEST_VENDOR);
    $signer->setSecurityKey($securityKey);

    return array_merge($fields, ['VPSSignature' => strtoupper($signer->buildSignature())]);
}

function notificationPath(string $isTest = 'true'): string
{
    return '/?callback=gf-sagepay&vendor=' . TEST_VENDOR . '&isTest=' . $isTest;
}

/**
 * Collects every Gravity Forms log line, whether or not logging is enabled.
 *
 * @return ArrayObject<int, string>
 */
function captureGravityFormsLog(): ArrayObject
{
    $lines = new ArrayObject();

    add_action('gform_pre_log_message', static function ($plugin, $message) use ($lines): void {
        $lines[] = (string) $message;
    }, 10, 2);

    return $lines;
}

function paymentStatus(Entry $entry): string
{
    return (string) GFAPI::get_entry($entry->getId())['payment_status'];
}

function completedTransactionCount(Entry $entry): int
{
    /** @var \wpdb $wpdb */
    $wpdb = $GLOBALS['wpdb'];

    return (int) $wpdb->get_var($wpdb->prepare(
        'SELECT COUNT(*) FROM %i WHERE lead_id = %d AND transaction_type = %s',
        $wpdb->prefix . 'gf_addon_payment_transaction',
        $entry->getId(),
        'payment',
    ));
}

/**
 * Asserts SagePay was told the notification is invalid and sent to the home page, with the entry left as it was.
 */
function expectRejected(Test_Response $response, Entry $entry): void
{
    $response->assertSee('Status=INVALID')->assertDontSee('gf-sagepay-token');
    expectRedirectUrl($response, home_url());

    expect(paymentStatus($entry))->toBe('Processing')
        ->and(freshEntryMeta($entry, 'final_transaction_reference'))->toBe('')
        ->and(freshEntryMeta($entry, 'gf_sagepay_token_hash'))->toBe('');
}

/**
 * Asserts the SagePay reply sends the customer to exactly this URL.
 */
function expectRedirectUrl(Test_Response $response, string $url): void
{
    expect((string) $response->get_content())->toMatch('/^RedirectUrl=' . preg_quote($url, '/') . '\r?$/m');
}
