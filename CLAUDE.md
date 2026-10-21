# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

WordPress plugin (Composer type `wordpress-plugin`) that adds a SagePay Server payment gateway to Gravity Forms via `GFPaymentAddOn`, using `omnipay/sagepay` (`SagePay\Server` gateway). One-off payments only: no refunds, voids, tokens, recurring, basket or deferred payments.

## Commands

```bash
composer install
composer style:check   # phpcs with the Itineris ruleset (phpcs.xml)
composer style:fix     # phpcbf
composer release:build # wipes vendor, installs --no-dev, zips to release/gf-sagepay.zip
                       # leaves vendor without phpcs; run composer install again afterwards
```

There is no test suite. CI (`.github/workflows/test.yml`) runs `style:check` on PHP 8.1 for pull requests only; branch pushes, including to `master`, do not trigger it. Tag pushes (`*.*.*`) trigger `release.yml`, which builds the zip, runs `php -l` on every file and creates a GitHub release.

`composer.json` pins `config.platform.php` to 8.1, so only use PHP 8.1-compatible syntax.

## Coding standards

- `phpcs.xml` applies the `Itineris` ruleset with text domain `gf-sagepay`.
- `src/AddOn.php` is exempt from camelCase method names and underscore property rules because it must match Gravity Forms' `GFPaymentAddOn` API (`_slug`, `redirect_url()`, `feed_settings_fields()`). Everywhere else uses camelCase.
- Keep logic out of `AddOn`; it delegates each Gravity Forms hook to a dedicated class.

## Architecture

Namespace `Itineris\SagePay\` maps to `src/` (PSR-4).

Bootstrap: `gf-sagepay.php` hooks `gform_loaded` (priority 5) to `GFSagePay::run()`, which loads the GF payment add-on framework, registers `AddOn` and initialises `ConfirmationHandler`.

Payment flow:

1. **Submit**: Gravity Forms calls `AddOn::redirect_url()`, which calls `RedirectUrlFactory::build()`. It generates a UUID `transaction_id` (sent to SagePay as `VendorTxCode`), marks the entry `Processing`, builds a `ServerPurchaseRequest` (card/address data from `CreditCardFactory` using feed field mappings) and stores SagePay's interim `transaction_reference` in entry meta. On failure the entry is marked `Failed` and the request `wp_die`s unless the `gf_sagepay_redirect_url_failure_wp_die` filter returns `false`.
2. **Notification**: SagePay POSTs to `home_url()?callback=gf-sagepay&vendor=...&isTest=...`. GF routes it to `AddOn::callback()`, which calls `CallbackHandler::run()`. The gateway is rebuilt from those query args (not from the feed; note `(bool) 'false'` is `true`, so `isTest` is always truthy here), the entry is located by `transaction_id`, the signature is checked with `isValid()`, the entry is marked paid, pending or failed by transaction status, and the handler replies to SagePay with the next URL then `exit`s.
3. **Return**: the next URL comes from `ConfirmationHandler::buildUrlFor()`: the entry's `source_url` plus `entry` and a single-use `gf-sagepay-token`. The token's HMAC-SHA512 hash (keyed by `wp_salt('auth')`) and expiry live in entry meta. On `wp` (priority 5) `ConfirmationHandler::maybeContinue()` validates and expires the token. If payment failed or timed out it redirects to the feed's cancel URL when one is set; otherwise (including failed payments with no cancel URL) it runs GF's normal confirmation (`GFFormDisplay::handle_confirmation`). `buildUrlFor()` is also a documented public API (see README).

Wrappers: `Entry` and `Feed` wrap GF entry and feed arrays. `Entry` writes properties through `GFAPI::update_entry_property()` and meta through `gform_update_meta()`, reloading itself after each write; the `*WithoutReload` variants batch writes before a single reload.

Gravity Forms Encrypted Fields workaround: `transaction_id` and `payment_amount` are written both as entry properties and entry meta. `CallbackHandler::getRawEntryByMeta()` falls back to a direct SQL `LIKE` search of the `transaction_reference` meta for the `VendorTxCode` when the property lookup fails. Preserve both writes when changing entry handling.

`RedirectUrlFactory` empties `$_FILES` while creating the Omnipay gateway (symfony/http-foundation conflict) and restores it afterwards so GF can still save uploads.

Feed configuration UI is defined in `FeedSettingsFields`; the `isTest` meta value `production` means live, anything else is the test environment.

## Version strings

The version appears in several places that are not kept in sync: the `gf-sagepay.php` header, `GFSagePay::VERSION`, `composer.json` `extra.branch-alias`, and minimum requirements in `MinimumRequirements` and the plugin header (which still say PHP 7.2 while Composer requires 8.1). Check all of them when bumping versions.

## Reference

Gravity Forms behaviour is often undocumented; read its source when in doubt. Key references: [GFPaymentAddOn](https://docs.gravityforms.com/gfpaymentaddon/), [Entry Object](https://docs.gravityforms.com/entry-object/), [omnipay-sagepay](https://github.com/thephpleague/omnipay-sagepay).

For sandbox testing, use `protxross` as the vendor code if test server IPs cannot be whitelisted, and expose local notification URLs with a tunnel such as ngrok.
