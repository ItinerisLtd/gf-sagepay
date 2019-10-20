# GF SagePay

[![CircleCI](https://circleci.com/gh/ItinerisLtd/gf-sagepay.svg?style=svg)](https://circleci.com/gh/ItinerisLtd/gf-sagepay)
[![Packagist Version](https://img.shields.io/packagist/v/itinerisltd/gf-sagepay.svg?label=release&style=flat-square)](https://packagist.org/packages/itinerisltd/gf-sagepay)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/rating/gf-sagepay?style=flat-square)](https://wordpress.org/plugins/gf-sagepay)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/itinerisltd/gf-sagepay.svg?style=flat-square)](https://packagist.org/packages/itinerisltd/gf-sagepay)
[![WordPress Plugin: Tested WP Version](https://img.shields.io/wordpress/plugin/tested/gf-sagepay?style=flat-square)](https://wordpress.org/plugins/gf-sagepay)
[![Packagist Downloads](https://img.shields.io/packagist/dt/itinerisltd/gf-sagepay.svg?label=packagist%20downloads&style=flat-square)](https://packagist.org/packages/itinerisltd/gf-sagepay/stats)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/gf-sagepay?label=wp.org%20downloads&style=flat-square)](https://wordpress.org/plugins/gf-sagepay/advanced/)
[![GitHub License](https://img.shields.io/github/license/itinerisltd/gf-sagepay.svg?style=flat-square)](https://github.com/ItinerisLtd/gf-sagepay/blob/master/LICENSE)
[![Hire Itineris](https://img.shields.io/badge/Hire-Itineris-ff69b4.svg?style=flat-square)](https://www.itineris.co.uk/contact/)
[![Twitter Follow @itineris_ltd](https://img.shields.io/twitter/follow/itineris_ltd?style=flat-square&color=1da1f2)](https://twitter.com/itineris_ltd)
[![Twitter Follow @TangRufus](https://img.shields.io/twitter/follow/TangRufus?style=flat-square&color=1da1f2)](https://twitter.com/tangrufus)

SagePay payment gateway for GravityForms.

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->


- [Goal](#goal)
- [Features](#features)
- [Not Supported / Not Implemented](#not-supported--not-implemented)
- [Minimum Requirements](#minimum-requirements)
- [Installation](#installation)
  - [Composer (Recommended)](#composer-recommended)
  - [wordpress.org (WP CLI)](#wordpressorg-wp-cli)
  - [wordpress.org](#wordpressorg)
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

Allow Gravity Forms accepts SagePay one-off payments via [SagePay Server](https://www.sagepay.co.uk/support/15/36/sage-pay-server-understanding-the-process). 

## Features

- [SagePay Server](https://www.sagepay.co.uk/support/15/36/sage-pay-server-understanding-the-process)
- [Gift Aid](https://www.sagepay.co.uk/support/12/36/gift-aid)
- [3D Secure](https://www.sagepay.co.uk/support/12/36/3d-secure-explained)
- [AVS/CV2](https://www.sagepay.co.uk/support/28/36/activating-adding-avs/cv2-rules)
- [Gravity Forms Logging](https://docs.gravityforms.com/logging-and-debugging/)
- [Gravity Forms Notification Events](https://docs.gravityforms.com/gravity-forms-notification-events/)
- [Gravity Forms Confirmation](https://docs.gravityforms.com/configuring-confirmations-in-gravity-forms/)
- [Gravity Forms Conditional Logic](https://docs.gravityforms.com/enable-conditional-logic/)

## Not Supported / Not Implemented

Although these features are not supported by this plugin, but you might able to do so via [MySagePay](https://live.sagepay.com/mysagepay/login.msp):
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

[Pull requests](https://github.com/ItinerisLtd/gf-sagepay) are welcomed.

## Minimum Requirements

- PHP v7.2
- PHP cURL Extension
- WordPress v4.9.10
- [Gravity Forms](https://www.gravityforms.com/) v2.4.14.4

## Installation

### Composer (Recommended)

```bash
composer require itinerisltd/gf-sagepay
```

### wordpress.org (WP CLI)

```bash
wp plugin install gf-sagepay
```

### wordpress.org

Download from https://wordpress.org/plugins/gf-sagepay 
Then, install `gf-sagepay.zip` [as usual](https://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

### Build from Source (Not Recommended)

```bash
# Make sure you use the same PHP version as remote servers.
# Building inside docker images is recommanded.
php -v

# Checkout source code
git clone https://github.com/ItinerisLtd/gf-sagepay.git
cd gf-sagepay
git checkout <the-tag-or-the-branch-or-the-commit>

# Build the zip file
composer release:build
```

Then, install `release/gf-sagepay.zip` [as usual](https://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

## Best Practices

### HTTPS Everywhere

Although SagePay accepts insecure HTTP sites, you should **always use HTTPS** to protect all communication.

### Payment Status

Always double check payment status on [MySagePay](https://live.sagepay.com/mysagepay/login.msp).

### Fraud Protection

To prevent chargebacks, enforce [3D Secure](https://www.sagepay.co.uk/support/12/36/3d-secure-explained) and [AVS/CV2](https://www.sagepay.co.uk/support/28/36/activating-adding-avs/cv2-rules) rules whenever possible.

## Test Sandbox

Always test the plugin and your fraud protection rules in test sandbox before going live.

If you can't whitelist test server IPs, use `protxross` as `Vendor Code`.

Use [ngrok](https://ngrok.com/) to make local notification URLs publicly accessible.

Use one of the [test credit cards](https://www.sagepay.co.uk/support/12/36/test-card-details-for-your-test-transactions).

## Common Issues

### Missing Gift Aid Acceptance Box

Only registered charities can use [Gift Aid](https://www.sagepay.co.uk/support/12/36/gift-aid) through the Sage Pay platform.
The gift aid acceptance box only appears if your vendor account is Gift Aid enabled and using **Donation** as transaction type.

### GF SagePay is Missing on Form Settings

Make sure you meet the [minimum requirements](#minimum-requirements). Check your environment details at the [System Status Page](https://docs.gravityforms.com/checking-environment-details/).

## Shipping Address

[OmniPay](https://omnipay.thephpleague.com/) requires both billing address and shipping address.

### Use case: Not delivering any physical goods

Map the shipping address fields to the billing ones.

### Use case: Allow ship to billing address

This is similar to the the WooCommerce way.

Use Gravity Forms' built-in feature: [Display option to use the values submitted in different field](https://docs.gravityforms.com/address-field/#advanced-settings)

## FAQ

### Will you add support for older PHP versions?

Never! This plugin will only work on [actively supported PHP versions](https://secure.php.net/supported-versions.php).

Don't use it on **end of life** or **security fixes only** PHP versions.

### It looks awesome. Where can I find more goodies like this?

- Articles on [Itineris' blog](https://www.itineris.co.uk/blog/)
- More projects on [Itineris' GitHub profile](https://github.com/itinerisltd)
- More plugins on [Itineris](https://profiles.wordpress.org/itinerisltd/#content-plugins) and [TangRufus](https://profiles.wordpress.org/tangrufus/#content-plugins) wp.org profiles
- Follow [@itineris_ltd](https://twitter.com/itineris_ltd) and [@TangRufus](https://twitter.com/tangrufus) on Twitter
- Hire [Itineris](https://www.itineris.co.uk/services/) to build your next awesome site

### Where can I give :star::star::star::star::star: reviews?

Thanks! Glad you like it. It's important to let my boss knows somebody is using this project. Please consider:

- leave a 5-star review on [wordpress.org](https://wordpress.org/support/plugin/gf-sagepay/reviews/)
- tweet something good with mentioning [@itineris_ltd](https://twitter.com/itineris_ltd) and [@TangRufus](https://twitter.com/tangrufus)
- :star: star this [Github repo](https://github.com/ItinerisLtd/gf-sagepay)
- :eyes: watch this [Github repo](https://github.com/ItinerisLtd/gf-sagepay)
- write blog posts
- submit [pull requests](https://github.com/ItinerisLtd/gf-sagepay)
- [hire Itineris](https://www.itineris.co.uk/services/)

## Developing

### Public API

#### Build URL for continuing confirmation

`ConfirmationHandler::buildUrlFor(Entry $entry, int $ttlInSeconds = 3600): string`

Usage:
```php
$entryId = 123;
$rawEntry = GFAPI::get_entry($entryId);
if (is_wp_error($rawEntry)) {
    wp_die('Entry not found');
}

$url = ConfirmationHandler::buildUrlFor(
    new Entry($rawEntry),
    86400 // expires in 24 hours (24*3600=86400)
);

echo $url;
// https://example.com?entry=123&gf-sagepay-token=XXXXXXXXXXXX
```

Use Case:
With ["using confirmation query strings to populate a form based on another submission"](https://docs.gravityforms.com/using-confirmation-query-strings-to-populate-a-form-based-on-another-submission/):
1. User fills in formA
1. User completes SagePay checkout form
1. User comes back and hits `CallbackHandler`
1. `CallbackHandler` sends user to formB according to confirmation settings
1. User arrives formB url with merged query strings

If the user quits before completing formB, you could use `ConfirmationHandler::buildUrlFor` generate a single-use, short-lived url for the user to resume formB.

Note:
- The url continues Gravity Forms confirmation
- Whoever got the url will go on confirmation, no authentication performed
- The confirmation will use latest field values from database which could have changed
- No payment status checking

#### Redirect URL Retrieval Failure Handling

After form submit, this plugin sends order information to SagePay in exchange for a redirect URL(the SagePay hosted checkout form URL).

By default, when redirect URL retrieval fails:
1. Mark entry payment status as `Failed`
1. [Log](https://docs.gravityforms.com/logging-and-debugging/) the error     
1. `wp_die` **immediately**

Common failure reasons:
- Incorrect vendor code
- Server IP not whitelisted

Tips: Check the [log](https://docs.gravityforms.com/logging-and-debugging/).


You can use `'gf_sagepay_redirect_url_failure_wp_die'` filter to:
- continue Gravity Forms' feed and confirmation flow
- perform extra operations
- redirect to a different error page

**Important:** If this filter returns `false`, normal Gravity Forms' feed and confirmation flow continues.
Improper settings might lead to disasters.

Example:
```php
add_filter('gf_sagepay_redirect_url_failure_wp_die', function(bool $shouldWpDie, ServerAuthorizeResponse $response, Entry $entry, GFPaymentAddOn $addOn): bool {

    // Do something.

    return true; // Do `wp_die`
    return false; // Don't `wp_die`, continue normal flow
    return $shouldWpDie; // Undecisive
}, 10, 4);
```

### Required Reading List

Read the followings before developing:
- [SagePay Server: Understanding the process](https://www.sagepay.co.uk/support/15/36/sage-pay-server-understanding-the-process)
- [SagePay Server integration kits, protocols and documents](https://www.sagepay.co.uk/support/find-an-integration-document/server-integration-documents)
- [Gravity Forms: GFPaymentAddOn](https://docs.gravityforms.com/gfpaymentaddon/)
- [Gravity Forms: Entry Object](https://docs.gravityforms.com/entry-object/)
- [Omnipay: Sage Pay](https://github.com/thephpleague/omnipay-sagepay)
- [thephpleague/omnipay-sagepay#45 (comment)](https://github.com/thephpleague/omnipay-sagepay/pull/45#issuecomment-150667423)
- [thephpleague/omnipay-sagepay#255 (comment)](https://github.com/thephpleague/omnipay/issues/255#issuecomment-90509446)

### Gravity Forms

Gravity Forms has undocumented hidden magics, read its source code.

### Testing

```bash
composer style:check
```

Pull requests without tests will not be accepted!

## Feedback

**Please provide feedback!** We want to make this library useful in as many projects as possible.
Please submit an [issue](https://github.com/ItinerisLtd/gf-sagepay/issues/new) and point out what you do and don't like, or fork the project and make suggestions.
**No issue is too small.**

## Change Log

Please see [CHANGELOG](./CHANGELOG.md) for more information on what has changed recently.

## Security

If you discover any security related issues, please email [dev@itineris.co.uk](mailto:dev@itineris.co.uk) instead of using the issue tracker.

## Credits

[GF SagePay](https://github.com/ItinerisLtd/gf-sagepay) is a [Itineris Limited](https://www.itineris.co.uk/) project created by [Tang Rufus](https://typist.tech).

Full list of contributors can be found [here](https://github.com/ItinerisLtd/gf-sagepay/graphs/contributors).

## License

[GF SagePay](https://github.com/ItinerisLtd/gf-sagepay) is released under the [MIT License](https://opensource.org/licenses/MIT).
