<?php

declare(strict_types=1);

use Mantle\Testing\Exceptions\WP_Die_Exception;

use function Itineris\SagePay\Tests\captureWpDieResponseCode;
use function Itineris\SagePay\Tests\confirmationPath;
use function Itineris\SagePay\Tests\confirmationShownFor;
use function Itineris\SagePay\Tests\createSagePayEntry;
use function Itineris\SagePay\Tests\freshEntryMeta;

it('shows the confirmation for a valid token and consumes it', function (): void {
    $entry = createSagePayEntry();

    $this->get(confirmationPath($entry));

    expect(confirmationShownFor($entry))->toBeTrue()
        ->and(freshEntryMeta($entry, 'gf_sagepay_token_hash'))->toBe('')
        ->and(freshEntryMeta($entry, 'gf_sagepay_token_used_hash'))->not->toBe('');
});

it('requests the return URL on the page the form was submitted from', function (): void {
    $entry = createSagePayEntry();

    expect(confirmationPath($entry))->toStartWith('/payment-form/?');
});

it('ignores requests whose entry or token is not a plain value', function (string $query): void {
    $entry = createSagePayEntry();
    parse_str((string) parse_url(confirmationPath($entry), PHP_URL_QUERY), $params);
    $hash = freshEntryMeta($entry, 'gf_sagepay_token_hash');

    $this->get('/?' . strtr($query, ['{id}' => $entry->getId(), '{token}' => $params['gf-sagepay-token']]));

    expect(confirmationShownFor($entry))->toBeFalse()
        ->and(freshEntryMeta($entry, 'gf_sagepay_token_hash'))->toBe($hash);
})->with([
    'array token' => ['entry={id}&gf-sagepay-token[]={token}'],
    'empty token' => ['entry={id}&gf-sagepay-token='],
    'array entry' => ['entry[]={id}&gf-sagepay-token={token}'],
    'negative entry' => ['entry=-{id}&gf-sagepay-token={token}'],
    'entry with trailing text' => ['entry={id}abc&gf-sagepay-token={token}'],
]);

it('ignores an expired token', function (): void {
    $entry = createSagePayEntry();

    $this->get(confirmationPath($entry, -1));

    expect(confirmationShownFor($entry))->toBeFalse();
});

it('says the link was already used when the same token comes back', function (bool $expired): void {
    $entry = createSagePayEntry();
    $path = confirmationPath($entry);
    $this->get($path);
    GFFormDisplay::$submission = [];

    if ($expired) {
        gform_update_meta($entry->getId(), 'gf_sagepay_token_expired_at', time() - 60);
    }

    $response = captureWpDieResponseCode();

    expect(fn () => $this->get($path))
        ->toThrow(WP_Die_Exception::class, 'This confirmation link has already been used.')
        ->and($response['response'])->toBe(200)
        ->and(confirmationShownFor($entry))->toBeFalse();
})->with([
    'before expiry' => [false],
    'after expiry' => [true],
]);

it('ignores a different token once one has been used', function (): void {
    $entry = createSagePayEntry();
    $this->get(confirmationPath($entry));
    GFFormDisplay::$submission = [];

    $this->get('/?entry=' . $entry->getId() . '&gf-sagepay-token=not-the-token');

    expect(confirmationShownFor($entry))->toBeFalse();
});

it('keeps the token valid when the form cannot be loaded', function (): void {
    $entry = createSagePayEntry();
    $path = confirmationPath($entry);
    $hash = freshEntryMeta($entry, 'gf_sagepay_token_hash');

    /** @var wpdb $wpdb */
    $wpdb = $GLOBALS['wpdb'];
    $wpdb->delete($wpdb->prefix . 'gf_form', ['id' => $entry->getFormId()]);
    GFFormsModel::flush_current_forms();
    GFCache::flush();

    expect(fn () => $this->get($path))->toThrow(WP_Die_Exception::class, 'Unable to locate form')
        ->and(freshEntryMeta($entry, 'gf_sagepay_token_hash'))->toBe($hash);
});

it('keeps the token valid when a failed payment has no feed', function (): void {
    $entry = createSagePayEntry(['payment_status' => 'Failed']);
    gform_delete_meta($entry->getId(), 'processed_feeds');
    $path = confirmationPath($entry);
    $hash = freshEntryMeta($entry, 'gf_sagepay_token_hash');

    expect(fn () => $this->get($path))->toThrow(WP_Die_Exception::class, 'Unable to locate feed')
        ->and(freshEntryMeta($entry, 'gf_sagepay_token_hash'))->toBe($hash);
});

it('redirects a failed payment to the feed cancel URL', function (): void {
    $entry = createSagePayEntry(['payment_status' => 'Failed'], ['cancelUrl' => 'https://example.com/cancelled']);

    $this->get(confirmationPath($entry))->assertRedirect('https://example.com/cancelled');

    expect(freshEntryMeta($entry, 'gf_sagepay_token_hash'))->toBe('');
});

it('follows a Gravity Forms redirect confirmation', function (): void {
    $entry = createSagePayEntry(form: [
        'confirmations' => [
            'redirect' => [
                'id' => 'redirect',
                'name' => 'Default Confirmation',
                'isDefault' => true,
                'type' => 'redirect',
                'url' => 'https://example.com/thank-you',
                'queryString' => '',
            ],
        ],
    ]);

    $this->get(confirmationPath($entry))->assertRedirect('https://example.com/thank-you');

    expect(freshEntryMeta($entry, 'gf_sagepay_token_hash'))->toBe('');
});

it('shows the confirmation for a failed payment without a cancel URL', function (): void {
    $entry = createSagePayEntry(['payment_status' => 'Failed']);

    $this->get(confirmationPath($entry));

    expect(confirmationShownFor($entry))->toBeTrue()
        ->and(freshEntryMeta($entry, 'gf_sagepay_token_hash'))->toBe('');
});

it('shows the confirmation for a paid entry created long ago', function (): void {
    $entry = createSagePayEntry(['date_created' => gmdate('Y-m-d H:i:s', time() - 2 * HOUR_IN_SECONDS)]);

    $this->get(confirmationPath($entry));

    expect(confirmationShownFor($entry))->toBeTrue()
        ->and(freshEntryMeta($entry, 'gf_sagepay_token_hash'))->toBe('');
});

it('asks the customer to retry when the token cannot be consumed', function (): void {
    $entry = createSagePayEntry();
    $path = confirmationPath($entry);
    $hash = freshEntryMeta($entry, 'gf_sagepay_token_hash');
    update_option('gf_submissions_block', time());
    $response = captureWpDieResponseCode();

    expect(fn () => $this->get($path))
        ->toThrow(WP_Die_Exception::class, 'Unable to confirm your payment right now.')
        ->and($response['response'])->toBe(503)
        ->and(freshEntryMeta($entry, 'gf_sagepay_token_hash'))->toBe($hash);
});
