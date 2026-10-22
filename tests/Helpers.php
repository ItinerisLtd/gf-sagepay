<?php

declare(strict_types=1);

namespace Itineris\SagePay\Tests;

use ArrayObject;
use GFAPI;
use GFFormDisplay;
use GuzzleHttp\Psr7\Response;
use Http\Client\HttpClient;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Strategy\DiscoveryStrategy;
use Http\Mock\Client as MockHttpClient;
use Itineris\SagePay\AddOn;
use Itineris\SagePay\ConfirmationHandler;
use Itineris\SagePay\Entry;
use Itineris\SagePay\Feed;
use Itineris\SagePay\RedirectUrlFactory;
use Mantle\Testing\Test_Response;
use Mantle\Testing\WP_Die;
use Omnipay\Common\Http\Client;
use Omnipay\SagePay\Message\ServerNotifyRequest;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use WP_Error;

use function array_map;
use function array_merge;
use function implode;
use function in_array;
use function is_array;
use function json_encode;
use function parse_str;
use function parse_url;
use function preg_quote;
use function range;
use function strtoupper;

const TEST_VENDOR = 'testvendor';
const TEST_SECURITY_KEY = 'SECURITYKEY';
const TEST_VPS_TX_ID = '{11111111-2222-3333-4444-555555555555}';
const TEST_CHECKOUT_URL = 'https://sandbox.opayo.eu.elavon.com/gateway/service/cardselection?vpstxid=' . TEST_VPS_TX_ID;

/**
 * Creates a form with an active SagePay feed and returns the form and feed IDs.
 *
 * @param array<string, mixed> $feedMeta Feed meta to override.
 * @param array<string, mixed> $form     Form properties to override.
 *
 * @return array{int, int}
 */
function createSagePayForm(array $feedMeta = [], array $form = []): array
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

    return [$formId, $feedId];
}

/**
 * Creates a form with a SagePay feed and an entry processed by that feed.
 *
 * @param array<string, mixed> $entry    Entry properties to override.
 * @param array<string, mixed> $feedMeta Feed meta to override.
 * @param array<string, mixed> $form     Form properties to override.
 */
function createSagePayEntry(array $entry = [], array $feedMeta = [], array $form = []): Entry
{
    [$formId, $feedId] = createSagePayForm($feedMeta, $form);

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

/**
 * Hands omnipay the current test's mock HTTP client through PSR-18 discovery, so no request ever reaches SagePay.
 */
final class FakeSagePayHttpStrategy implements DiscoveryStrategy
{
    private static ?MockHttpClient $client = null;

    /**
     * @return array<int, array{class: callable}>
     */
    public static function getCandidates($type): array
    {
        $client = self::$client;
        if (! $client instanceof MockHttpClient || ! in_array($type, [ClientInterface::class, HttpClient::class], true)) {
            return [];
        }

        return [['class' => static fn (): MockHttpClient => $client]];
    }

    public static function use(MockHttpClient $client): void
    {
        self::$client = $client;

        if (! in_array(self::class, (array) Psr18ClientDiscovery::getStrategies(), true)) {
            Psr18ClientDiscovery::prependStrategy(self::class);
        }

        Psr18ClientDiscovery::clearCache();
    }

    public static function reset(): void
    {
        if (! self::$client instanceof MockHttpClient) {
            return;
        }

        self::$client = null;
        Psr18ClientDiscovery::clearCache();
    }
}

/**
 * Replaces SagePay's HTTP endpoint with a mock client until the test ends.
 */
function fakeSagePayHttp(): MockHttpClient
{
    $client = new MockHttpClient();
    FakeSagePayHttpStrategy::use($client);

    return $client;
}

/**
 * SagePay's reply to a transaction registration.
 */
function registrationResponse(string $status = 'OK', string $securityKey = TEST_SECURITY_KEY): Response
{
    return new Response(200, [], implode("\r\n", [
        'VPSProtocol=4.00',
        'Status=' . $status,
        'StatusDetail=' . ('OK' === $status ? '2014 : The Transaction was Registered Successfully.' : '3048 : The CardNumber length is invalid.'),
        'VPSTxId=' . TEST_VPS_TX_ID,
        'SecurityKey=' . $securityKey,
        'NextURL=' . TEST_CHECKOUT_URL,
    ]));
}

/**
 * Creates an entry as Gravity Forms hands it to the add-on on submission, before registration.
 *
 * @param array<string, mixed> $feedMeta Feed meta to override.
 */
function createUnpaidSagePayEntry(array $feedMeta = []): Entry
{
    return createSagePayEntry(['payment_status' => '', 'currency' => 'GBP'], $feedMeta);
}

/**
 * Registers a 10.00 payment for the entry through its feed and returns the checkout URL.
 */
function registerPayment(Entry $entry): string
{
    $addOn = AddOn::get_instance();

    return RedirectUrlFactory::build($addOn, new Feed($addOn->get_payment_feed($entry->toArray())), $entry, 10.0);
}

/**
 * Reads the registration request omnipay last sent, and the query of the notification URL inside it.
 *
 * @return array{array<string, mixed>, array<string, mixed>}
 */
function sentRegistration(MockHttpClient $http): array
{
    $request = $http->getLastRequest();
    if (! $request instanceof RequestInterface) {
        throw new RuntimeException('No registration request was sent to SagePay.');
    }

    parse_str((string) $request->getBody(), $sent);
    parse_str((string) parse_url((string) ($sent['NotificationURL'] ?? ''), PHP_URL_QUERY), $notificationQuery);

    return [$sent, $notificationQuery];
}

/**
 * Submits a payment form whose feed maps every customer and address field, and returns what SagePay was sent.
 *
 * @param array<string, string> $address Address inputs 3.1 to 3.6, keyed by input number.
 *
 * @return array{array<string, mixed>, array<string, string>}
 */
function submitSagePayForm(string $addressType, array $address): array
{
    $http = fakeSagePayHttp();
    $http->addResponse(registrationResponse());

    $addressMapping = [];
    foreach (['address' => 1, 'address2' => 2, 'city' => 3, 'state' => 4, 'zip' => 5, 'country' => 6] as $key => $input) {
        $addressMapping['billingInformation_' . $key] = '3.' . $input;
        $addressMapping['shippingInformation_' . $key] = '3.' . $input;
    }

    [$formId] = createSagePayForm(
        array_merge([
            'transactionType' => 'product',
            'paymentAmount' => 'form_total',
            'description' => 'Test payment',
            'customerInformation_firstName' => '1.3',
            'customerInformation_lastName' => '1.6',
            'customerInformation_email' => '2',
        ], $addressMapping),
        ['fields' => [
            ['id' => 1, 'type' => 'name', 'label' => 'Name', 'nameFormat' => 'advanced', 'inputs' => [
                ['id' => '1.3', 'label' => 'First'],
                ['id' => '1.6', 'label' => 'Last'],
            ]],
            ['id' => 2, 'type' => 'email', 'label' => 'Email'],
            ['id' => 3, 'type' => 'address', 'label' => 'Address', 'addressType' => $addressType, 'inputs' => array_map(
                static fn (int $input): array => ['id' => '3.' . $input, 'label' => 'Address ' . $input],
                range(1, 6),
            )],
            ['id' => 4, 'type' => 'product', 'inputType' => 'singleproduct', 'label' => 'Donation', 'basePrice' => '$10.00', 'inputs' => [
                ['id' => '4.1', 'label' => 'Name'],
                ['id' => '4.2', 'label' => 'Price'],
                ['id' => '4.3', 'label' => 'Quantity'],
            ]],
            ['id' => 5, 'type' => 'total', 'label' => 'Total'],
        ]],
    );

    $values = [
        'input_1_3' => 'Ada',
        'input_1_6' => 'Lovelace',
        'input_2' => 'ada@example.org',
        'input_4_1' => 'Donation',
        'input_4_2' => '$10.00',
        'input_4_3' => '1',
    ];
    foreach ($address as $input => $value) {
        $values['input_3_' . $input] = $value;
    }

    $result = GFAPI::submit_form($formId, $values);
    if ($result instanceof WP_Error) {
        throw new RuntimeException($result->get_error_message());
    }

    if (true !== $result['is_valid']) {
        throw new RuntimeException('Submission failed validation: ' . json_encode($result['validation_messages'] ?? []));
    }

    [$sent] = sentRegistration($http);

    return [$result, $sent];
}
