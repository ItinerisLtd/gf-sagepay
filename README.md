# GF SagePay

[![GitHub Actions](https://github.com/ItinerisLtd/gf-sagepay/actions/workflows/test.yml/badge.svg)](https://github.com/ItinerisLtd/gf-sagepay)
[![Packagist Version](https://img.shields.io/packagist/v/itinerisltd/gf-sagepay.svg?label=release&style=flat-square)](https://packagist.org/packages/itinerisltd/gf-sagepay)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/itinerisltd/gf-sagepay.svg?style=flat-square)](https://packagist.org/packages/itinerisltd/gf-sagepay)
[![Packagist Downloads](https://img.shields.io/packagist/dt/itinerisltd/gf-sagepay.svg?label=packagist%20downloads&style=flat-square)](https://packagist.org/packages/itinerisltd/gf-sagepay/stats)
[![GitHub License](https://img.shields.io/github/license/itinerisltd/gf-sagepay.svg?style=flat-square)](https://github.com/ItinerisLtd/gf-sagepay/blob/master/LICENSE)
[![Hire Itineris](https://img.shields.io/badge/Hire-Itineris-ff69b4.svg?style=flat-square)](https://www.itineris.co.uk/contact/)
[![Twitter Follow @itineris_ltd](https://img.shields.io/twitter/follow/itineris_ltd?style=flat-square&color=1da1f2)](https://x.com/itineris_ltd)
[![Twitter Follow @TangRufus](https://img.shields.io/twitter/follow/TangRufus?style=flat-square&color=1da1f2)](https://x.com/tangrufus)

SagePay payment gateway for GravityForms.

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->

- [Goal](#goal)
- [Features](#features)
- [Not Supported / Not Implemented](#not-supported--not-implemented)
- [Minimum Requirements](#minimum-requirements)
- [Upgrading to 1.0](#upgrading-to-10)
- [Installation](#installation)
  - [Composer (Recommended)](#composer-recommended)
  - [Build from Source (Not Recommended)](#build-from-source-not-recommended)
- [Best Practices](#best-practices)
  - [HTTPS Everywhere](#https-everywhere)
  - [Payment Status](#payment-status)
  - [Fraud Protection](#fraud-protection)
- [Test Sandbox](#test-sandbox)
- [Common Issues](#common-issues)
  - [Missing Gift Aid Acceptance Box](#missing-gift-aid-acceptance-box)
  - [GF SagePay is Missing on Form Settings](#gf-sagepay-is-missing-on-form-settings)
- [Shipping Address](#shipping-address)
  - [Use case: Not delivering any physical goods](#use-case-not-delivering-any-physical-goods)
  - [Use case: Allow ship to billing address](#use-case-allow-ship-to-billing-address)
- [FAQ](#faq)
  - [Will you add support for older PHP versions?](#will-you-add-support-for-older-php-versions)
  - [It looks awesome. Where can I find more goodies like this?](#it-looks-awesome-where-can-i-find-more-goodies-like-this)
  - [Where can I give :star::star::star::star::star: reviews?](#where-can-i-give-starstarstarstarstar-reviews)
- [Developing](#developing)
  - [Public API](#public-api)
    - [Build URL for continuing confirmation](#build-url-for-continuing-confirmation)
    - [Redirect URL Retrieval Failure Handling](#redirect-url-retrieval-failure-handling)
  - [Required Reading List](#required-reading-list)
  - [Gravity Forms](#gravity-forms)
  - [Testing](#testing)
- [Feedback](#feedback)
- [Change Log](#change-log)
- [Security](#security)
- [Credits](#credits)
- [License](#license)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## Goal

Allow Gravity Forms to accept SagePay (now Opayo by Elavon) one-off payments via [SagePay Server](https://www.elavon.co.uk/content/dam/elavon/en-gb/v2/documents/resource-center/opayo/my-opayo---sagepay/troubleshooting/SERVER_Integration_and_Protocol_Guidelines_270815.pdf).

## Features

- [SagePay Server](https://www.elavon.co.uk/content/dam/elavon/en-gb/v2/documents/resource-center/opayo/my-opayo---sagepay/troubleshooting/SERVER_Integration_and_Protocol_Guidelines_270815.pdf)
- [Gift Aid](https://www.elavon.co.uk/customer-centre/help-with-your-solutions/opayo.html)
- [3D Secure](https://www.elavon.co.uk/customer-centre/help-with-your-solutions/opayo/fraud-prevention/3D-Secure.html)
  - Strong Customer Authentication (SCA) is handled on Opayo's hosted payment pages (Server protocol 4.00), so card details and 3D Secure challenges never touch your site. See [Fraud Protection](#fraud-protection) for the feed setting
- [AVS/CV2](https://www.elavon.co.uk/customer-centre/help-with-your-solutions/opayo/fraud-prevention/AVS-CV2.html)
- [Gravity Forms Logging](https://docs.gravityforms.com/logging-and-debugging/)
- [Gravity Forms Notification Events](https://docs.gravityforms.com/gravity-forms-notification-events/)
- [Gravity Forms Confirmation](https://docs.gravityforms.com/confirmation-type-page/)
- [Gravity Forms Conditional Logic](https://docs.gravityforms.com/enable-conditional-logic/)
- Payment statuses: SagePay's notification sets the entry's payment status and fires the matching [Gravity Forms notification events](https://docs.gravityforms.com/gravity-forms-notification-events/) (payment completed, pending or failed)
  - `OK` or `OK REPEATED` → **Paid**
  - `PENDING` → **Pending**
  - `NOTAUTHED`, `REJECTED`, `ABORT` or `ERROR` → **Failed**
  - Repeated notifications are ignored, and a Paid entry is never downgraded

## Not Supported / Not Implemented

These features are not supported by this plugin, but you might be able to use them via [MySagePay](https://live.sagepay.com/mysagepay/login.msp):
- Card reference
- Token billing
- Deferred payment
- Recurring payment
- Void
- Refund
- Abort
- Basket
- Surcharges
- Account Type M – for telephone (MOTO) transactions
- Account Type C – for repeat transactions

[Pull requests](https://github.com/ItinerisLtd/gf-sagepay) are welcome.

## Minimum Requirements

- PHP v8.4.1
- PHP bcmath Extension
- PHP cURL Extension
- WordPress v6.7
- [Gravity Forms](https://www.gravityforms.com/) v3.1

## Upgrading to 1.0

1.0.0 is a major release. Before upgrading from 0.12:

1. Check the [minimum requirements](#minimum-requirements): PHP 8.4.1 with bcmath, WordPress 6.7 and Gravity Forms 3.1.
1. If your project pins `config.platform.php` in `composer.json`, set it to `8.4.1` or higher (`8.4` means 8.4.0 and is refused).
1. Update the Composer constraint, letting only gf-sagepay's own dependencies move (it needs `omnipay/common` 3.5.1 or newer):
   ```sh
   composer require itinerisltd/gf-sagepay:^1.0 --update-with-dependencies
   ```
1. If you extend any of the plugin's classes or call methods outside the [public API](#public-api), update that code:
   - every class is now `final`, `protected` members are `private`, and everything outside the public API is `@internal`
   - `Entry::isTimeout()`, `Entry::expireConfirmationTokenNow()`, `Entry::setProperty()` and `Feed::isActive()` are removed
   - `GatewayFactory::build()` is private (use `GatewayFactory::buildFromFeed()`)
   - `Entry::setMeta()` requires a `string` key, and `CallbackHandler::run()` now ends the request (`never`)

Good to know:

- Existing feeds, entries and settings carry over unchanged.
- Gravity Forms only enforces its minimum version in wp-admin. A site still on Gravity Forms 2.x keeps taking payments, but the SagePay settings pages stay hidden until Gravity Forms is updated.
- If a theme or another plugin bundles its own Guzzle, that copy may be the one loaded. Any Guzzle 7 release works.
- The notification URL sent to SagePay is now `?callback=gf-sagepay` (previously `?callback=gf-sagepay&vendor=<vendor code>&isTest=true|false`). Notifications are verified against the vendor code stored when the payment was registered, and any `vendor` or `isTest` query args are ignored.

## Installation

### Composer (Recommended)

```bash
composer require itinerisltd/gf-sagepay
```

### Build from Source (Not Recommended)

```bash
# Make sure you use the same PHP version as remote servers.
# Building inside docker images is recommended.
php -v

# Checkout source code
git clone https://github.com/ItinerisLtd/gf-sagepay.git
cd gf-sagepay
git checkout <the-tag-or-the-branch-or-the-commit>

# Build the zip file
composer release:build
```

Then, install `release/gf-sagepay.zip` [as usual](https://wordpress.org/documentation/article/manage-plugins/).

## Best Practices

### HTTPS Everywhere

Although SagePay accepts insecure HTTP sites, you should **always use HTTPS** to protect all communication.

### Payment Status

Always double check payment status on [MySagePay](https://live.sagepay.com/mysagepay/login.msp).

### Fraud Protection

To prevent chargebacks, enforce [3D Secure](https://www.elavon.co.uk/customer-centre/help-with-your-solutions/opayo/fraud-prevention/3D-Secure.html) and [AVS/CV2](https://www.elavon.co.uk/customer-centre/help-with-your-solutions/opayo/fraud-prevention/AVS-CV2.html) rules whenever possible.

Each feed's 3D-Secure setting decides how 3D Secure applies to its payments. New feeds default to "Use MSP Setting", which follows the 3D Secure rules in MySagePay; existing feeds keep their saved value, so check them after upgrading. Avoid "Disable", since card issuers can decline unauthenticated payments under SCA, and note that "Force Ignoring Rules" skips your MySagePay 3D Secure rules.

## Test Sandbox

Always test the plugin and your fraud protection rules in the test sandbox before going live.

Your own test account only accepts registrations from IP addresses whitelisted in MySagePay (otherwise error 4020). If you can't whitelist yours, use the shared `protxross` vendor code, which accepts registrations from any IP.

SagePay must be able to reach the notification URL, so a local site needs a public tunnel. A [Cloudflare quick tunnel](https://developers.cloudflare.com/cloudflare-one/networks/connectors/cloudflare-tunnel/do-more-with-tunnels/trycloudflare/) works without an account, but SagePay only reaches it over plain `http://`; [ngrok](https://ngrok.com/) is an alternative.

Use one of the [test cards](https://developer.elavon.com/products/en-uk/opayo/v1/test-in-sandbox), for example Visa `4929000000006` with CVV `123`, or `4929602110085639` to simulate a decline.

## Common Issues

### Missing Gift Aid Acceptance Box

Only registered charities can use [Gift Aid](https://www.elavon.co.uk/customer-centre/help-with-your-solutions/opayo.html) through the Sage Pay platform.
The gift aid acceptance box only appears if your vendor account is Gift Aid enabled and using **Donation** as transaction type.

### GF SagePay is Missing on Form Settings

Make sure you meet the [minimum requirements](#minimum-requirements). Check your environment details at the [System Status Page](https://docs.gravityforms.com/checking-environment-details/).

## Shipping Address

SagePay requires both a billing address and a delivery address, so map both sets of fields in the feed (a missing delivery country fails with error 3140).

### Use case: Not delivering any physical goods

Map the shipping address fields to the billing ones.

### Use case: Allow ship to billing address

This is similar to the WooCommerce way.

Use Gravity Forms' built-in feature: [Display option to use the values submitted in different field](https://docs.gravityforms.com/address-field/#advanced-settings)

## FAQ

### Will you add support for older PHP versions?

Never! This plugin will only work on [actively supported PHP versions](https://www.php.net/supported-versions.php).

Don't use it on **end of life** or **security fixes only** PHP versions.

### It looks awesome. Where can I find more goodies like this?

- Articles on [Itineris' blog](https://www.itineris.co.uk/blog/)
- More projects on [Itineris' GitHub profile](https://github.com/itinerisltd)
- More plugins on [Itineris](https://profiles.wordpress.org/itinerisltd/#content-plugins) and [TangRufus](https://profiles.wordpress.org/tangrufus/#content-plugins) wp.org profiles
- Follow [@itineris_ltd](https://x.com/itineris_ltd) and [@TangRufus](https://x.com/tangrufus) on Twitter
- Hire [Itineris](https://www.itineris.co.uk/services/) to build your next awesome site

### Where can I give :star::star::star::star::star: reviews?

Thanks! Glad you like it. It's important to let us know somebody is using this project. Please consider:

- tweet something good with mentioning [@itineris_ltd](https://x.com/itineris_ltd) and [@TangRufus](https://x.com/tangrufus)
- :star: star this [Github repo](https://github.com/ItinerisLtd/gf-sagepay)
- :eyes: watch this [Github repo](https://github.com/ItinerisLtd/gf-sagepay)
- write blog posts
- submit [pull requests](https://github.com/ItinerisLtd/gf-sagepay)
- [hire Itineris](https://www.itineris.co.uk/services/)

## Developing

### Public API

These are covered by semantic versioning, so they only change in a major release:
- the `gf_sagepay_redirect_url_failure_wp_die` and `gf_sagepay_redirect_url_exception_wp_die` filters
- `ConfirmationHandler::buildUrlFor()`
- `Itineris\SagePay\Entry`: the constructor and `getId()`, `getFormId()`, `getProperty()`, `getMeta()`, `toArray()` and `isPaidOrPending()`

Everything else may change in any release.
That includes every other class and method (all classes are `final`, and internals are marked `@internal`), the `gf_sagepay_terminate` action, `GFSagePay::VERSION` (read the version from the plugin header instead) and the entry meta keys the plugin writes (`gf_sagepay_*` and `transaction_reference`).

The failure filter passes omnipay's `Omnipay\SagePay\Message\ServerAuthorizeResponse`, so a major upgrade of `omnipay/sagepay` would be a breaking change for this plugin.

#### Build URL for continuing confirmation

`Itineris\SagePay\ConfirmationHandler::buildUrlFor(Itineris\SagePay\Entry $entry, int $ttlInSeconds = 3600): string`

Usage:
```php
$entryId = 123;
$rawEntry = GFAPI::get_entry($entryId);
if (is_wp_error($rawEntry)) {
    wp_die('Entry not found');
}

$url = \Itineris\SagePay\ConfirmationHandler::buildUrlFor(
    new \Itineris\SagePay\Entry($rawEntry),
    86400 // expires in 24 hours (24*3600=86400)
);

echo $url;
// https://example.com/the-form-page/?entry=123&gf-sagepay-token=XXXXXXXXXXXX (the entry's source page)
```

Use Case:
With ["using confirmation query strings to populate a form based on another submission"](https://docs.gravityforms.com/confirmation-query-string-populate-form/):
1. User fills in formA
1. User completes the SagePay checkout form
1. SagePay notifies `CallbackHandler`, which records the payment and replies with a single-use confirmation URL
1. SagePay sends the user to that URL, where `ConfirmationHandler` runs formA's confirmation and sends the user to formB
1. User arrives at formB's URL with merged query strings

If the user quits before completing formB, you could use `ConfirmationHandler::buildUrlFor` to generate a single-use, short-lived url for the user to resume formB.

Note:
- The url continues Gravity Forms confirmation
- Whoever got the url will go on confirmation, no authentication performed
- Each call replaces the entry's previous token, so any earlier url for the same entry (including the one SagePay sends the customer to) stops working
- The confirmation will use latest field values from database which could have changed
- A payment that is not Paid or Pending is sent to the feed's cancel URL when one is set

#### Redirect URL Retrieval Failure Handling

After form submission, this plugin sends the order to SagePay in exchange for a redirect URL (the SagePay hosted checkout form URL).

By default, when redirect URL retrieval fails:
1. Mark entry payment status as `Failed`
1. [Log](https://docs.gravityforms.com/logging-and-debugging/) the error
1. `wp_die` **immediately**

Common failure reasons:
- Incorrect vendor code
- Server IP not whitelisted (error 4020)

Tips: Check the [log](https://docs.gravityforms.com/logging-and-debugging/).


You can use `'gf_sagepay_redirect_url_failure_wp_die'` filter to:
- continue Gravity Forms' feed and confirmation flow
- perform extra operations
- redirect to a different error page

**Important:** If this filter returns `false`, normal Gravity Forms' feed and confirmation flow continues.
Improper settings might lead to disasters.

Example:
```php
add_filter('gf_sagepay_redirect_url_failure_wp_die', function(bool $shouldWpDie, \Omnipay\SagePay\Message\ServerAuthorizeResponse $response, \Itineris\SagePay\Entry $entry, \GFPaymentAddOn $addOn): bool {

    // Do something.

    return true; // Do `wp_die`
    return false; // Don't `wp_die`, continue normal flow
    return $shouldWpDie; // Keep the default
}, 10, 4);
```

When registration ends in an exception instead of a reply from SagePay (for example SagePay cannot be reached, or omnipay cannot build the request), there is no `ServerAuthorizeResponse`. The entry is still marked as `Failed` with the exception in its notes, and the `'gf_sagepay_redirect_url_exception_wp_die'` filter decides whether to `wp_die`. It receives the exception instead of the response, and returning `false` has the same effect as above:

```php
add_filter('gf_sagepay_redirect_url_exception_wp_die', function(bool $shouldWpDie, \Throwable $exception, \Itineris\SagePay\Entry $entry, \GFPaymentAddOn $addOn): bool {
    return $shouldWpDie;
}, 10, 4);
```

### Required Reading List

Read the following before developing:
- [SagePay Server: Understanding the process](https://www.elavon.co.uk/content/dam/elavon/en-gb/v2/documents/resource-center/opayo/my-opayo---sagepay/troubleshooting/SERVER_Integration_and_Protocol_Guidelines_270815.pdf)
- [SagePay Server integration kits, protocols and documents](https://www.elavon.co.uk/customer-centre/help-with-your-solutions/opayo.html)
- [Gravity Forms: GFPaymentAddOn](https://docs.gravityforms.com/gfpaymentaddon/)
- [Gravity Forms: Entry Object](https://docs.gravityforms.com/entry-object/)
- [Omnipay: Sage Pay](https://github.com/thephpleague/omnipay-sagepay)
- [thephpleague/omnipay-sagepay#45 (comment)](https://github.com/thephpleague/omnipay-sagepay/pull/45#issuecomment-150667423)
- [thephpleague/omnipay#255 (comment)](https://github.com/thephpleague/omnipay/issues/255#issuecomment-90509446)

### Gravity Forms

Gravity Forms has undocumented behaviour; read its source code when in doubt.

### Testing

```bash
composer style:check   # coding standards
composer test          # Pest integration suite (real WordPress, Gravity Forms and MySQL)
tests/e2e/run.sh       # Playwright payments against Opayo's sandbox (wp-env, Docker and cloudflared)
```

See [CLAUDE.md](./CLAUDE.md#testing) for the local setup each one needs.

Pull requests without tests will not be accepted!

## Feedback

**Please provide feedback!** We want to make this library useful in as many projects as possible.
Please submit an [issue](https://github.com/ItinerisLtd/gf-sagepay/issues/new) and point out what you do and don't like, or fork the project and make suggestions.
**No issue is too small.**

## Change Log

Please see [CHANGELOG](./CHANGELOG.md) for more information on what has changed recently.

## Security

If you discover any security related issues, please email [dev@itineris.co.uk](mailto:dev@itineris.co.uk) instead of using the issue tracker.
You can also report it privately through GitHub's [private vulnerability reporting](https://github.com/ItinerisLtd/gf-sagepay/security/advisories/new).

## Credits

[GF SagePay](https://github.com/ItinerisLtd/gf-sagepay) is an [Itineris Limited](https://www.itineris.co.uk/) project created by [Tang Rufus](https://typist.tech) and maintained by [Lee Hanbury-Pickett](https://github.com/codepuncher).

Full list of contributors can be found [here](https://github.com/ItinerisLtd/gf-sagepay/graphs/contributors).

## License

[GF SagePay](https://github.com/ItinerisLtd/gf-sagepay) is released under the [MIT License](https://opensource.org/license/MIT).
