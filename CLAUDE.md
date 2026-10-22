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
composer test          # Pest integration suite (see Testing)
tests/e2e/run.sh       # Playwright payments against Opayo's sandbox (see Testing)
```

CI (`.github/workflows/test.yml`) runs on pull requests only; branch pushes, including to `master`, do not trigger it. It runs `style:check` on PHP 8.4, the integration suite against WordPress 6.7 and latest, and the `e2e` job (real payments against Opayo's sandbox). The integration and e2e jobs install Gravity Forms with the org secret `PRIVATE_PACKAGIST_TOKEN` (an Actions and a Dependabot secret); fork PRs don't receive it, so those checks fail. Tag pushes (`*.*.*`) trigger `release.yml`, which builds the zip, runs `php -l` on every file and creates a GitHub release.

## Testing

Integration tests use Pest with `alleyinteractive/pest-plugin-wordpress` (Mantle testkit), real WordPress and real Gravity Forms. `tests/Pest.php` rsyncs the plugin into a throwaway WordPress install (`WP_CORE_DIR`, default `/tmp/wordpress`), copies Gravity Forms in next to it, activates both and re-runs Pest from there.

- Gravity Forms comes from `tests/gravityforms`, a separate Composer project that pulls `gravity/gravityforms` through Private Packagist. Install it with `composer install --working-dir=tests/gravityforms` (needs Private Packagist credentials), or point `GRAVITYFORMS_PATH` at an existing copy. Never cache or commit its `vendor/`.
- MySQL is required; SQLite is not supported by Gravity Forms. Locally: `docker run -d -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=wordpress_unit_tests -p 3307:3306 mysql:8`, then `WP_DB_HOST=127.0.0.1:3307 WP_DB_USER=root WP_DB_PASSWORD=root WP_SKIP_DB_CREATE=true composer test`.
- `tests/TestCase.php` uses Mantle's `Refresh_Database`, so each test runs in a rolled-back transaction; create fixtures in `beforeEach`, not `beforeAll`. Its `tearDown()` also clears `GFFormDisplay::$submission` and all per-submission payment state on the add-on singleton (`redirect_url`, `is_payment_gateway`, `authorization`, `current_submission_data`, `current_feed`, `_single_submission_feed`), which would otherwise leak between tests.
- `tests/bootstrap-hooks.php` makes `gf_sagepay_terminate` throw `Exit_Simulation_Exception`, disables asynchronous notifications and empties the `gf_*` tables once per run. Mantle turns `wp_die()` into `WP_Die_Exception` and `wp_redirect()` into `WP_Redirect_Exception`.
- `tests/Helpers.php`: `createSagePayForm()` builds a form with an active SagePay feed; `createSagePayEntry()` adds an entry processed by that feed; `createPendingSagePayEntry()` adds the transaction ID and reference left by registration; `signedNotification()` builds notification fields signed with omnipay's own `buildSignature()` and `notificationPath()` gives the callback URL, with `legacyNotificationPath()` adding the ignored `vendor` and `isTest` args (both use the shared `TEST_*` constants); `feedIdOf()` returns an entry's feed ID; `captureWpDieResponseCode()` records the HTTP status passed to `wp_die()`; `captureGravityFormsLog()` collects log lines through `gform_pre_log_message`. `fakeSagePayHttp()` gives omnipay a `php-http/mock-client` through PSR-18 discovery for the rest of the test (`TestCase::tearDown()` resets it), so registration requests get queued responses such as `registrationResponse()` and never reach SagePay; `createUnpaidSagePayEntry()` and `registerPayment()` drive `RedirectUrlFactory::build()` the way submission does; `submitSagePayForm()` submits a real form through `GFAPI::submit_form()` with every customer and address field mapped; `sentRegistration()` reads back the request sent to SagePay and its notification URL query.
- Gravity Forms rejects `@example.com` and `@domain.com` email addresses on submission, so form submissions in tests use `example.org`.
- `tests/e2e/` is a separate npm project (Node latest LTS via `.nvmrc`) that pays through Opayo's real sandbox with Playwright. `run.sh` rsyncs throwaway copies of the plugin's runtime files (with their own `composer install --no-dev`), Gravity Forms, `mu-plugins/` and `site/` (whose `fixture.php` builds the form, feed and pages) into the gitignored `build/`, which is all `.wp-env.json` mounts, because the site is public while the tunnel is open. It starts wp-env (core from `wordpress.org/latest.zip`, so commands don't query api.wordpress.org), sets a random admin password generated inside the container (never on a command line, as CI logs are public), opens a Cloudflare quick tunnel (three attempts), sets `WP_HOME`/`WP_SITEURL` to it in wp-config (wp-env otherwise adds its port) and runs `payment.spec.ts`: a paid card, a decline then a successful retry, three declines, and a cancel on the card page. Locally it needs Docker, the `cloudflared` binary on `PATH`, `npm ci` and `npx playwright install chromium` in `tests/e2e`, plus Composer and Gravity Forms installed; set `E2E_KEEP_ENV=1` to keep the containers for debugging. wp-env caches the downloaded WordPress, so run `npx wp-env start --update` in `tests/e2e` to pick up a newer release locally.
- Sandbox facts the e2e suite relies on: the shared vendor `protxross` registers from any IP (the `sandbox` vendor answers 4020); SagePay's notifications reach a quick tunnel only over plain `http://` (HTTPS never arrives), so the site uses the tunnel's `http://` URL and Chromium runs with `--disable-features=HttpsUpgrades`; the tunnel uses `--protocol http2` because QUIC is often blocked; Opayo's card pages reject values set from script (error 5003), so card details are typed; the feed must map delivery fields (error 3140 otherwise); a declined card keeps the customer on Opayo's card selection page with no notification, and only the third failed attempt notifies (the plugin then marks the entry Failed). Failure artifacts are public on this repo, so fixtures use fake data only.
- `tests/` is excluded from phpcs and from the release zip.

`composer.json` pins `config.platform.php` to 8.4.1, so only use PHP 8.4-compatible syntax.

## Coding standards

- `phpcs.xml` applies the `Itineris` ruleset with text domain `gf-sagepay`.
- `src/AddOn.php` is exempt from camelCase method names and underscore property rules because it must match Gravity Forms' `GFPaymentAddOn` API (`_slug`, `redirect_url()`, `feed_settings_fields()`). Everywhere else uses camelCase.
- Never add types to values that come from Gravity Forms (entry and feed arrays, meta values, `rgar()`/`rgars()` results). `AddOn` is also exempt from the Slevomat property and parameter type-hint sniffs, because its parents are untyped and adding types would be a fatal signature error. Elsewhere, suppress those sniffs per parameter with `@phpcsSuppress` in the docblock. The Slevomat type-hint sniffs are `phpcs-only` in `phpcs.xml`, so `composer style:fix` never adds native types.
- Keep logic out of `AddOn`; it delegates each Gravity Forms hook to a dedicated class.
- Public API (semver, listed in the README): the `gf_sagepay_redirect_url_failure_wp_die` and `gf_sagepay_redirect_url_exception_wp_die` filters, `ConfirmationHandler::buildUrlFor()`, and `Entry`'s constructor, `getId()`, `getFormId()`, `getProperty()`, `getMeta()`, `toArray()` and `isPaidOrPending()`. Changing any of these needs a major version. Every class is `final`; everything else is marked `@internal` and uses `private` rather than `protected` (except `AddOn`'s Gravity Forms properties).

## Architecture

Namespace `Itineris\SagePay\` maps to `src/` (PSR-4).

Bootstrap: `gf-sagepay.php` hooks `gform_loaded` (priority 5) to `GFSagePay::run()`, which loads the GF payment add-on framework, registers `AddOn` and initialises `ConfirmationHandler`.

Payment flow:

1. **Submit**: Gravity Forms calls `AddOn::redirect_url()`, which calls `RedirectUrlFactory::build()`. It generates a UUID `transaction_id` (sent to SagePay as `VendorTxCode`), marks the entry `Processing`, builds a `ServerPurchaseRequest` (card/address data from `CreditCardFactory` using feed field mappings) and stores SagePay's interim `transaction_reference` in entry meta. On failure the entry is marked `Failed` and the request `wp_die`s unless the `gf_sagepay_redirect_url_failure_wp_die` filter returns `false`. `gf_sagepay_redirect_url_failure_wp_die` receives the `ServerAuthorizeResponse`. Any exception while building the gateway or sending the registration also marks the entry `Failed`, with the exception class and message in the entry note, and `gf_sagepay_redirect_url_exception_wp_die` (which receives the exception) decides whether to `wp_die`.
2. **Notification**: SagePay POSTs to `home_url()?callback=gf-sagepay`. GF routes it to `AddOn::callback()`, which calls `CallbackHandler::run()`. Any `vendor` or `isTest` query args are ignored. `getVerifiedEntry()` locates the entry by `transaction_id`, checks its stored `transaction_reference` belongs to that transaction, and checks the signature with `isValid()` using the vendor code `RedirectUrlFactory` stored in the `gf_sagepay_vendor` entry meta at registration, or the entry's feed's vendor code (via `Feed::forEntry()`, inactive feeds included) when none is stored. Without a vendor code, and on any other failure, it replies `INVALID` with `home_url()` and leaves the entry unchanged. `applyStatus()` then marks the entry paid, pending or failed, skipping a status it already has and never downgrading a paid entry, and the handler replies `OK` with the next URL. Both replies end with `Terminator::terminate()`, which fires the `gf_sagepay_terminate` action (so a test harness can throw from it instead), then closes the connection with `fastcgi_finish_request()` or `litespeed_finish_request()` where available (with `ignore_user_abort(true)`, as `wp-cron.php` does) so shutdown work such as Gravity Forms' queued notifications does not delay the reply, then `exit`s; keep `reject()` outside the `try`, otherwise a throwing listener would be caught and produce a second reply. The SagePay signature is never logged.
3. **Return**: the next URL comes from `ConfirmationHandler::buildUrlFor()`: the entry's `source_url` plus `entry` and a single-use `gf-sagepay-token`. The token's HMAC-SHA512 hash (keyed by `wp_salt('auth')`) and expiry live in entry meta. On `wp` (priority 5) `ConfirmationHandler::maybeContinue()` validates the token, then loads what the response needs (the feed's cancel URL for a payment that is not Paid or Pending, otherwise the form), and only then consumes the token by deleting its hash with `gform_delete_meta()`. Just before the delete it records the hash as `gf_sagepay_token_used_hash`. Only the request whose delete removes a row continues. A concurrent duplicate, or any later request with the same token (even after it expires), gets an "already used" message; any other token gets no response. It then redirects to the cancel URL when one is set, or runs GF's normal confirmation (`GFFormDisplay::handle_confirmation`), including for failed payments with no cancel URL.

Notifications are parsed by `NotifyGateway` (from `GatewayFactory::buildForNotification()`) into `NotifyRequest`, which overrides omnipay's `isValid()` to compare signatures with `hash_equals()` rather than `===`; omnipay calls `isValid()` again inside `getTransactionStatus()` and `confirm()`, so the override keeps every check constant time.

Wrappers: `Entry` and `Feed` wrap GF entry and feed arrays. `Entry` writes properties through `GFAPI::update_entry_property()` and meta through `gform_update_meta()`, reloading itself after each write; the `*WithoutReload` variants batch writes before a single reload.

Gravity Forms Encrypted Fields workaround: `transaction_id` and `payment_amount` are written both as entry properties and entry meta. `CallbackHandler::getRawEntryByMeta()` falls back to a direct SQL `LIKE` search of the `transaction_reference` meta for the `VendorTxCode` when the property lookup fails. Preserve both writes when changing entry handling.

`RedirectUrlFactory` empties `$_FILES` while creating the Omnipay gateway (symfony/http-foundation conflict) and restores it in a `finally` so GF can still save uploads, even if gateway creation throws.

Feed configuration UI is defined in `FeedSettingsFields`; the `isTest` meta value `production` means live, anything else is the test environment.

## Version strings

The version appears in several places that are not kept in sync: the `gf-sagepay.php` header, `GFSagePay::VERSION`, `composer.json` `extra.branch-alias`, and minimum requirements in `MinimumRequirements` and the plugin header. Check all of them when bumping versions.

## Reference

Gravity Forms behaviour is often undocumented; read its source when in doubt. Key references: [GFPaymentAddOn](https://docs.gravityforms.com/gfpaymentaddon/), [Entry Object](https://docs.gravityforms.com/entry-object/), [omnipay-sagepay](https://github.com/thephpleague/omnipay-sagepay).

For sandbox testing, use `protxross` as the vendor code if test server IPs cannot be whitelisted, and expose local notification URLs with a tunnel such as ngrok.
