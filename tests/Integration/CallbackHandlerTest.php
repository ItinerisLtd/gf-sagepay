<?php

declare(strict_types=1);

use Itineris\SagePay\Entry;

use function Itineris\SagePay\Tests\captureGravityFormsLog;
use function Itineris\SagePay\Tests\completedTransactionCount;
use function Itineris\SagePay\Tests\createPendingSagePayEntry;
use function Itineris\SagePay\Tests\expectRedirectUrl;
use function Itineris\SagePay\Tests\expectRejected;
use function Itineris\SagePay\Tests\feedIdOf;
use function Itineris\SagePay\Tests\freshEntryMeta;
use function Itineris\SagePay\Tests\legacyNotificationPath;
use function Itineris\SagePay\Tests\notificationPath;
use function Itineris\SagePay\Tests\paymentStatus;
use function Itineris\SagePay\Tests\signedNotification;

use const Itineris\SagePay\Tests\TEST_SECURITY_KEY;
use const Itineris\SagePay\Tests\TEST_VENDOR;

it('marks the entry paid and replies with the confirmation URL', function (): void {
    $entry = createPendingSagePayEntry();

    $this->post(notificationPath(), signedNotification($entry->getProperty('transaction_id')))
        ->assertSee('Status=OK')
        ->assertSee('gf-sagepay-token=');

    expect(paymentStatus($entry))->toBe('Paid')
        ->and(completedTransactionCount($entry))->toBe(1)
        ->and(freshEntryMeta($entry, 'final_transaction_reference'))->not->toBe('');
});

it('applies the status SagePay reports', function (string $status, string $expected): void {
    $entry = createPendingSagePayEntry();

    $this->post(notificationPath(), signedNotification($entry->getProperty('transaction_id'), fields: ['Status' => $status]))
        ->assertSee('Status=OK');

    expect(paymentStatus($entry))->toBe($expected);
})->with([
    'pending' => ['PENDING', 'Pending'],
    'rejected' => ['REJECTED', 'Failed'],
    'aborted' => ['ABORT', 'Failed'],
]);

it('does not repeat a payment that is already recorded', function (): void {
    $entry = createPendingSagePayEntry();
    $fields = signedNotification($entry->getProperty('transaction_id'));

    $this->post(notificationPath(), $fields);
    $this->post(notificationPath(), $fields)->assertSee('Status=OK');

    expect(paymentStatus($entry))->toBe('Paid')
        ->and(completedTransactionCount($entry))->toBe(1);
});

it('never downgrades a paid entry', function (): void {
    $entry = createPendingSagePayEntry();
    $transactionId = $entry->getProperty('transaction_id');
    $this->post(notificationPath(), signedNotification($transactionId));

    $this->post(notificationPath(), signedNotification($transactionId, fields: ['Status' => 'REJECTED']))
        ->assertSee('Status=OK');

    expect(paymentStatus($entry))->toBe('Paid');
});

it('finds the entry through the transaction reference when the property lookup misses', function (): void {
    $entry = createPendingSagePayEntry();
    $transactionId = $entry->getProperty('transaction_id');
    GFAPI::update_entry_property($entry->getId(), 'transaction_id', '');

    $this->post(notificationPath(), signedNotification($transactionId))->assertSee('Status=OK');

    expect(paymentStatus($entry))->toBe('Paid');
});

it('rejects a notification with a bad signature', function (): void {
    $entry = createPendingSagePayEntry();
    $fields = signedNotification($entry->getProperty('transaction_id'));
    $fields['VPSSignature'] = str_repeat('0', 32);

    expectRejected($this->post(notificationPath(), $fields), $entry);
});

it('rejects a notification signed without the stored security key', function (): void {
    $entry = createPendingSagePayEntry();
    gform_update_meta($entry->getId(), 'transaction_reference', json_encode(['VendorTxCode' => $entry->getProperty('transaction_id')]));

    expectRejected($this->post(notificationPath(), signedNotification($entry->getProperty('transaction_id'), '')), $entry);
});

it('rejects a notification whose stored reference belongs to another transaction', function (): void {
    $entry = createPendingSagePayEntry();
    gform_update_meta($entry->getId(), 'transaction_reference', json_encode([
        'SecurityKey' => TEST_SECURITY_KEY,
        'VendorTxCode' => wp_generate_uuid4(),
    ]));

    expectRejected($this->post(notificationPath(), signedNotification($entry->getProperty('transaction_id'))), $entry);
});

it('rejects a transaction code that is not one of ours', function (string $transactionId): void {
    $entry = createPendingSagePayEntry();

    expectRejected($this->post(notificationPath(), signedNotification($transactionId)), $entry);
})->with([
    'percent sign' => ['%'],
    'not a UUID' => ['abc'],
    'unknown UUID' => ['0f8fad5b-d9cb-469f-a165-70867728950e'],
]);

it('rejects a notification with non-string fields', function (): void {
    $entry = createPendingSagePayEntry();
    $fields = signedNotification($entry->getProperty('transaction_id'));
    $fields['VPSSignature'] = ['x'];

    expectRejected($this->post(notificationPath(), $fields), $entry);
});

it('ignores the vendor and environment in the notification URL', function (): void {
    $entry = createPendingSagePayEntry();

    $this->post(legacyNotificationPath('othervendor', 'nonsense'), signedNotification($entry->getProperty('transaction_id')))
        ->assertSee('Status=OK');

    expect(paymentStatus($entry))->toBe('Paid');
});

it('rejects a notification signed for another vendor', function (): void {
    $entry = createPendingSagePayEntry();
    $fields = signedNotification($entry->getProperty('transaction_id'), vendor: 'othervendor');

    expectRejected($this->post(legacyNotificationPath('othervendor', 'true'), $fields), $entry);
});

it('verifies with the vendor code stored at registration whatever happens to the feed', function (Closure $changeFeed): void {
    $entry = createPendingSagePayEntry();
    $changeFeed($entry);

    $this->post(notificationPath(), signedNotification($entry->getProperty('transaction_id')))->assertSee('Status=OK');

    expect(paymentStatus($entry))->toBe('Paid');
})->with([
    'vendor code changed' => [static fn (Entry $entry) => GFAPI::update_feed(
        feedIdOf($entry),
        ['feedName' => 'SagePay', 'vendor' => 'newvendor', 'isTest' => 'test'],
    )],
    'feed deleted' => [static fn (Entry $entry) => GFAPI::delete_feed(feedIdOf($entry))],
    'no processed feeds' => [static fn (Entry $entry) => gform_delete_meta($entry->getId(), 'processed_feeds')],
]);

it('verifies an entry without a stored vendor code against its feed', function (array $feedMeta, string $vendor, bool $deactivate): void {
    $entry = createPendingSagePayEntry(feedMeta: $feedMeta);
    gform_delete_meta($entry->getId(), 'gf_sagepay_vendor');
    if ($deactivate) {
        GFAPI::update_feed_property(feedIdOf($entry), 'is_active', 0);
    }

    $this->post(notificationPath(), signedNotification($entry->getProperty('transaction_id'), vendor: $vendor))
        ->assertSee('Status=OK');

    expect(paymentStatus($entry))->toBe('Paid');
})->with([
    'feed vendor code' => [[], TEST_VENDOR, false],
    'custom vendor code' => [['vendor' => 'gf_custom', 'vendor_custom' => 'customvendor'], 'customvendor', false],
    'inactive feed' => [[], TEST_VENDOR, true],
]);

it('rejects a notification when no vendor code is known for the entry', function (Closure $removeVendor): void {
    $entry = createPendingSagePayEntry();
    gform_delete_meta($entry->getId(), 'gf_sagepay_vendor');
    $removeVendor($entry);
    $log = captureGravityFormsLog();

    expectRejected($this->post(notificationPath(), signedNotification($entry->getProperty('transaction_id'))), $entry);
    expect(implode("\n", $log->getArrayCopy()))->toContain('No vendor code for entry ' . $entry->getId());
})->with([
    'feed deleted' => [static fn (Entry $entry) => GFAPI::delete_feed(feedIdOf($entry))],
    'no processed feeds' => [static fn (Entry $entry) => gform_delete_meta($entry->getId(), 'processed_feeds')],
    'empty custom vendor code' => [static fn (Entry $entry) => GFAPI::update_feed(
        feedIdOf($entry),
        ['feedName' => 'SagePay', 'vendor' => 'gf_custom', 'vendor_custom' => '', 'isTest' => 'test'],
    )],
]);

it('confirms a genuine payment even if a later step fails', function (): void {
    $entry = createPendingSagePayEntry();
    add_action('gform_post_payment_completed', static function (): void {
        throw new RuntimeException('Fulfilment failed');
    });

    $response = $this->post(notificationPath(), signedNotification($entry->getProperty('transaction_id')))
        ->assertSee('Status=OK')
        ->assertDontSee('gf-sagepay-token');
    expectRedirectUrl($response, home_url());

    expect(paymentStatus($entry))->toBe('Paid');
});

it('keeps the security key and signature out of the log', function (): void {
    $entry = createPendingSagePayEntry('LOGGEDKEY');
    $fields = signedNotification($entry->getProperty('transaction_id'), 'LOGGEDKEY');
    $log = captureGravityFormsLog();

    $this->post(notificationPath(), $fields)->assertSee('Status=OK');

    $all = implode("\n", $log->getArrayCopy());
    expect($all)->toContain($entry->getProperty('transaction_id'))
        ->not->toContain('LOGGEDKEY')
        ->not->toContain($fields['VPSSignature'])
        ->not->toContain('gf-sagepay-token');
});
