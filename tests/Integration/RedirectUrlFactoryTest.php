<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Request;
use Http\Client\Exception\NetworkException;
use Itineris\SagePay\Tests\FakeSagePayHttpStrategy;
use Mantle\Testing\Exceptions\WP_Die_Exception;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Http\Exception as HttpException;

use function Itineris\SagePay\Tests\createUnpaidSagePayEntry;
use function Itineris\SagePay\Tests\fakeSagePayHttp;
use function Itineris\SagePay\Tests\freshEntryMeta;
use function Itineris\SagePay\Tests\notificationPath;
use function Itineris\SagePay\Tests\paymentStatus;
use function Itineris\SagePay\Tests\registerPayment;
use function Itineris\SagePay\Tests\registrationResponse;
use function Itineris\SagePay\Tests\sentRegistration;
use function Itineris\SagePay\Tests\signedNotification;

use const Itineris\SagePay\Tests\TEST_CHECKOUT_URL;
use const Itineris\SagePay\Tests\TEST_SECURITY_KEY;
use const Itineris\SagePay\Tests\TEST_VENDOR;

it('registers the payment with SagePay and returns its checkout URL', function (): void {
    $http = fakeSagePayHttp();
    $http->addResponse(registrationResponse());
    $entry = createUnpaidSagePayEntry();

    expect(registerPayment($entry))->toBe(TEST_CHECKOUT_URL);

    $transactionId = (string) $entry->getProperty('transaction_id');
    [$sent] = sentRegistration($http);

    expect(wp_is_uuid($transactionId))->toBeTrue()
        ->and(paymentStatus($entry))->toBe('Processing')
        ->and(freshEntryMeta($entry, 'transaction_id'))->toBe($transactionId)
        ->and(json_decode(freshEntryMeta($entry, 'transaction_reference'), true))
        ->toMatchArray(['SecurityKey' => TEST_SECURITY_KEY, 'VendorTxCode' => $transactionId])
        ->and($sent)->toMatchArray([
            'Vendor' => TEST_VENDOR,
            'VendorTxCode' => $transactionId,
            'Amount' => '10.00',
            'Currency' => 'GBP',
        ]);
});

it('registers with the environment the feed selects', function (string $environment, string $endpoint): void {
    $http = fakeSagePayHttp();
    $http->addResponse(registrationResponse());

    registerPayment(createUnpaidSagePayEntry(['isTest' => $environment]));
    [$sent, $notificationQuery] = sentRegistration($http);

    expect((string) $http->getLastRequest()->getUri())->toStartWith($endpoint)
        ->and($sent['NotificationURL'])->toStartWith(home_url())
        ->and($notificationQuery)->toBe(['callback' => 'gf-sagepay']);
})->with([
    'test' => ['test', 'https://sandbox.opayo.eu.elavon.com/'],
    'production' => ['production', 'https://live.opayo.eu.elavon.com/'],
]);

it('registers with a custom vendor code', function (): void {
    $http = fakeSagePayHttp();
    $http->addResponse(registrationResponse());

    $entry = createUnpaidSagePayEntry(['vendor' => 'gf_custom', 'vendor_custom' => 'customvendor']);
    registerPayment($entry);
    [$sent] = sentRegistration($http);

    expect($sent['Vendor'])->toBe('customvendor')
        ->and(freshEntryMeta($entry, 'gf_sagepay_vendor'))->toBe('customvendor');
});

it('marks the entry failed and stops when SagePay rejects the registration', function (): void {
    fakeSagePayHttp()->addResponse(registrationResponse('INVALID'));
    $entry = createUnpaidSagePayEntry();

    expect(fn () => registerPayment($entry))
        ->toThrow(WP_Die_Exception::class, 'Failed to retrieve SagePay checkout form URL')
        ->and(paymentStatus($entry))->toBe('Failed');
});

it('returns no URL instead of stopping when the failure filter says so', function (): void {
    fakeSagePayHttp()->addResponse(registrationResponse('INVALID'));
    add_filter('gf_sagepay_redirect_url_failure_wp_die', '__return_false');
    $entry = createUnpaidSagePayEntry();

    expect(registerPayment($entry))->toBe('')
        ->and(paymentStatus($entry))->toBe('Failed');
});

it('marks the entry failed and stops when SagePay cannot be reached', function (): void {
    fakeSagePayHttp()->addException(new NetworkException('Could not resolve host', new Request('POST', 'https://sandbox.opayo.eu.elavon.com/')));
    $entry = createUnpaidSagePayEntry();

    expect(fn () => registerPayment($entry))
        ->toThrow(WP_Die_Exception::class, 'Failed to retrieve SagePay checkout form URL')
        ->and(paymentStatus($entry))->toBe('Failed')
        ->and(freshEntryMeta($entry, 'transaction_reference'))->toBe('')
        ->and(array_column(GFFormsModel::get_lead_notes($entry->getId()), 'value'))
        ->toContain('Itineris\\SagePay\\RedirectUrlFactory::handleException(): Unable to register the payment with SagePay - Omnipay\\Common\\Http\\Exception\\NetworkException: Could not resolve host');
});

it('marks the entry failed when omnipay cannot build the request', function (): void {
    $http = fakeSagePayHttp();
    $entry = createUnpaidSagePayEntry();
    $filtered = new ArrayObject();
    add_filter('gf_sagepay_redirect_url_exception_wp_die', static function (bool $shouldWpDie, Throwable $exception) use ($filtered): bool {
        $filtered[] = $exception;

        return false;
    }, 10, 2);

    expect(registerPayment($entry, -10.0))->toBe('')
        ->and(paymentStatus($entry))->toBe('Failed')
        ->and($filtered[0] ?? null)->toBeInstanceOf(InvalidRequestException::class)
        ->and($http->getRequests())->toBeEmpty();
});

it('returns no URL instead of stopping when the exception filter says so', function (): void {
    fakeSagePayHttp()->addException(new NetworkException('Could not resolve host', new Request('POST', 'https://sandbox.opayo.eu.elavon.com/')));
    $filtered = new ArrayObject();
    add_filter('gf_sagepay_redirect_url_exception_wp_die', static function (bool $shouldWpDie, Throwable $exception) use ($filtered): bool {
        $filtered[] = $exception;

        return false;
    }, 10, 2);
    $entry = createUnpaidSagePayEntry();

    expect(registerPayment($entry))->toBe('')
        ->and(paymentStatus($entry))->toBe('Failed')
        ->and($filtered->getArrayCopy())->toHaveCount(1)
        ->and($filtered[0])->toBeInstanceOf(HttpException::class);
});

it('registers with uploaded files present and leaves them for Gravity Forms', function (): void {
    fakeSagePayHttp()->addResponse(registrationResponse());
    $previous = $_FILES;
    $files = ['input_5' => ['name' => 'a.pdf', 'type' => 'application/pdf', 'tmp_name' => '/nonexistent/upload', 'error' => 0, 'size' => 10]];
    $_FILES = $files;

    try {
        expect(registerPayment(createUnpaidSagePayEntry()))->toBe(TEST_CHECKOUT_URL)
            ->and($_FILES)->toBe($files);
    } finally {
        $_FILES = $previous;
    }
});

it('marks the entry failed and leaves uploaded files when the gateway cannot be built', function (): void {
    FakeSagePayHttpStrategy::use(new RuntimeException('No HTTP client available'));
    $previous = $_FILES;
    $files = ['input_5' => ['name' => 'a.pdf', 'type' => 'application/pdf', 'tmp_name' => '/nonexistent/upload', 'error' => 0, 'size' => 10]];
    $_FILES = $files;

    try {
        $entry = createUnpaidSagePayEntry();

        expect(fn () => registerPayment($entry))->toThrow(WP_Die_Exception::class)
            ->and($_FILES)->toBe($files)
            ->and(paymentStatus($entry))->toBe('Failed');
    } finally {
        $_FILES = $previous;
    }
});

it('accepts the notification SagePay sends for the registered payment', function (): void {
    fakeSagePayHttp()->addResponse(registrationResponse(securityKey: 'ROUNDTRIPKEY'));
    $entry = createUnpaidSagePayEntry();
    registerPayment($entry);

    $this->post(notificationPath(), signedNotification((string) $entry->getProperty('transaction_id'), 'ROUNDTRIPKEY'))
        ->assertSee('Status=OK');

    expect(paymentStatus($entry))->toBe('Paid');
});
