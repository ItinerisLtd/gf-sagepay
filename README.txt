=== GF SagePay ===

Contributors: itinerisltd, tangrufus
Tags: gravity forms, gravityforms, payment-gateway, payment gateway, sagepay, sage pay
Requires at least: 4.9.5
Tested up to: 5.3
Requires PHP: 7.2
Stable tag: 0.10.9
License: MIT
License URI: https://opensource.org/licenses/MIT

SagePay payment gateway for GravityForms.

== Description ==

### Goal

Allow Gravity Forms accepts SagePay one-off payments via [SagePay Server](https://www.sagepay.co.uk/support/15/36/sage-pay-server-understanding-the-process).

### Features

- [SagePay Server](https://www.sagepay.co.uk/support/15/36/sage-pay-server-understanding-the-process)
- [Gift Aid](https://www.sagepay.co.uk/support/12/36/gift-aid)
- [3D Secure](https://www.sagepay.co.uk/support/12/36/3d-secure-explained)
- [AVS/CV2](https://www.sagepay.co.uk/support/28/36/activating-adding-avs/cv2-rules)
- [Gravity Forms Logging](https://docs.gravityforms.com/logging-and-debugging/)
- [Gravity Forms Notification Events](https://docs.gravityforms.com/gravity-forms-notification-events/)
- [Gravity Forms Confirmation](https://docs.gravityforms.com/configuring-confirmations-in-gravity-forms/)
- [Gravity Forms Conditional Logic](https://docs.gravityforms.com/enable-conditional-logic/)

### Not Supported / Not Implemented

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

### Best Practices

#### HTTPS Everywhere

Although SagePay accepts insecure HTTP sites, you should **always use HTTPS** to protect all communication.

#### Payment Status

Always double check payment status on [MySagePay](https://live.sagepay.com/mysagepay/login.msp).

#### Fraud Protection

To prevent chargebacks, enforce [3D Secure](https://www.sagepay.co.uk/support/12/36/3d-secure-explained) and [AVS/CV2](https://www.sagepay.co.uk/support/28/36/activating-adding-avs/cv2-rules) rules whenever possible.

### Test Sandbox

Always test the plugin and your fraud protection rules in test sandbox before going live.

If you can't whitelist test server IPs, use `protxross` as `Vendor Code`.

Use [ngrok](https://ngrok.com/) to make local notification URLs publicly accessible.

Use one of the [test credit cards](https://www.sagepay.co.uk/support/12/36/test-card-details-for-your-test-transactions).

### Common Issues

#### Missing Gift Aid Acceptance Box

Only registered charities can use [Gift Aid](https://www.sagepay.co.uk/support/12/36/gift-aid) through the Sage Pay platform.
The gift aid acceptance box only appears if your vendor account is Gift Aid enabled and using **Donation** as transaction type.

#### GF SagePay is Missing on Form Settings

Make sure you meet the [minimum requirements](#minimum-requirements). Check your environment details at the [System Status Page](https://docs.gravityforms.com/checking-environment-details/).

### Shipping Address

[OmniPay](https://omnipay.thephpleague.com/) requires both billing address and shipping address.

#### Use case: Not delivering any physical goods

Map the shipping address fields to the billing ones.

#### Use case: Allow ship to billing address

This is similar to the the WooCommerce way.

Use Gravity Forms' built-in feature: [Display option to use the values submitted in different field](https://docs.gravityforms.com/address-field/#advanced-settings)

### For Developers

- Check the public API and customization on [GitHub](https://github.com/ItinerisLtd/gf-sagepay#developing)
- Fork the plugin on [GitHub](https://github.com/ItinerisLtd/gf-sagepay).

== Frequently Asked Questions ==

### Minimum Requirements

- PHP v7.2
- PHP cURL Extension
- WordPress v4.9.5
- [Gravity Forms](https://www.gravityforms.com/) v2.4.14.4

### Will you add support for older PHP versions?

Never! This plugin will only work on [actively supported PHP versions](https://secure.php.net/supported-versions.php).

Don't use it on **end of life** or **security fixes only** PHP versions.

### It looks awesome. Where can I find more goodies like this?

- Articles on [Itineris' blog](https://www.itineris.co.uk/blog/)
- More projects on [Itineris' GitHub profile](https://github.com/itinerisltd)
- More plugins on [Itineris](https://profiles.wordpress.org/itinerisltd/#content-plugins) and [TangRufus](https://profiles.wordpress.org/tangrufus/#content-plugins) wp.org profiles
- Follow [@itineris_ltd](https://twitter.com/itineris_ltd) and [@TangRufus](https://twitter.com/tangrufus) on Twitter
- Hire [Itineris](https://www.itineris.co.uk/services/) to build your next awesome site

### Where can I give ★★★★★ reviews?

Thanks! Glad you like it. It's important to let my boss knows somebody is using this project. Please consider:

- leave a 5-star review on [wordpress.org](https://wordpress.org/support/plugin/gf-sagepay/reviews/)
- tweet something good with mentioning [@itineris_ltd](https://twitter.com/itineris_ltd) and [@TangRufus](https://twitter.com/tangrufus)
- ★ star this [Github repo](https://github.com/ItinerisLtd/gf-sagepay)
- watch this [Github repo](https://github.com/ItinerisLtd/gf-sagepay)
- write blog posts
- submit [pull requests](https://github.com/ItinerisLtd/gf-sagepay)
- [hire Itineris](https://www.itineris.co.uk/services/)

### Where to report security related issues?

If you discover any security related issues, please email [dev@itineris.co.uk](mailto:dev@itineris.co.uk) instead of using the issue tracker.

== Changelog ==

Please see [CHANGELOG](https://github.com/ItinerisLtd/gf-sagepay/blob/master/CHANGELOG.md) for more information on what has changed recently.
