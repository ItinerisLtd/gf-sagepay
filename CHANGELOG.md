# Changelog

## [1.0.1](https://github.com/ItinerisLtd/gf-sagepay/compare/1.0.0...1.0.1) (2026-10-29)


### Bug Fixes

* **ci:** drop Tailscale from CI jobs ([1c61372](https://github.com/ItinerisLtd/gf-sagepay/commit/1c613728aada1387190f63aa960d83c799540b1d))
* **ci:** drop Tailscale from CI jobs ([9f5d136](https://github.com/ItinerisLtd/gf-sagepay/commit/9f5d136a55f247a6b81271f0be20dfb624789757))

## [1.0.0](https://github.com/itinerisltd/gf-sagepay/tree/1.0.0) (2026-10-23)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.12.1...1.0.0)

**Merged pull requests:**

- test\(e2e\): cover declined, retried and cancelled sandbox payments [\#273](https://github.com/ItinerisLtd/gf-sagepay/pull/273) ([codepuncher](https://github.com/codepuncher))
- test\(e2e\): cover a sandbox payment with playwright [\#272](https://github.com/ItinerisLtd/gf-sagepay/pull/272) ([codepuncher](https://github.com/codepuncher))
- refactor\(api\): define the public API for 1.0.0 [\#271](https://github.com/ItinerisLtd/gf-sagepay/pull/271) ([codepuncher](https://github.com/codepuncher))
- chore\(requirements\): require gravity forms 3.1 [\#270](https://github.com/ItinerisLtd/gf-sagepay/pull/270) ([codepuncher](https://github.com/codepuncher))
- fix\(callback\): close the notification response before shutdown work [\#269](https://github.com/ItinerisLtd/gf-sagepay/pull/269) ([codepuncher](https://github.com/codepuncher))
- test\(isolation\): reset add-on submission state between tests [\#268](https://github.com/ItinerisLtd/gf-sagepay/pull/268) ([codepuncher](https://github.com/codepuncher))
- fix\(callback\): compare notification signatures in constant time [\#267](https://github.com/ItinerisLtd/gf-sagepay/pull/267) ([codepuncher](https://github.com/codepuncher))
- fix\(callback\): verify notifications with the registered vendor code [\#266](https://github.com/ItinerisLtd/gf-sagepay/pull/266) ([codepuncher](https://github.com/codepuncher))
- fix\(redirect\): mark entry failed when registration throws [\#265](https://github.com/ItinerisLtd/gf-sagepay/pull/265) ([codepuncher](https://github.com/codepuncher))
- test\(integration\): cover submission, vendors and token races [\#264](https://github.com/ItinerisLtd/gf-sagepay/pull/264) ([codepuncher](https://github.com/codepuncher))
- test\(redirect\): cover payment registration [\#263](https://github.com/ItinerisLtd/gf-sagepay/pull/263) ([codepuncher](https://github.com/codepuncher))
- test\(callback\): cover notification handling [\#262](https://github.com/ItinerisLtd/gf-sagepay/pull/262) ([codepuncher](https://github.com/codepuncher))
- test\(confirmation\): cover the return flow [\#261](https://github.com/ItinerisLtd/gf-sagepay/pull/261) ([codepuncher](https://github.com/codepuncher))
- test\(integration\): add pest integration suite infrastructure [\#260](https://github.com/ItinerisLtd/gf-sagepay/pull/260) ([codepuncher](https://github.com/codepuncher))
- refactor\(callback\): route termination through a hook [\#259](https://github.com/ItinerisLtd/gf-sagepay/pull/259) ([codepuncher](https://github.com/codepuncher))
- chore\(deps\): update omnipay dependencies and require php 8.4.1 [\#258](https://github.com/ItinerisLtd/gf-sagepay/pull/258) ([codepuncher](https://github.com/codepuncher))
- fix\(callback\): verify notifications before updating entries [\#257](https://github.com/ItinerisLtd/gf-sagepay/pull/257) ([codepuncher](https://github.com/codepuncher))
- fix\(confirmation\): show already used on refresh instead of the empty form [\#256](https://github.com/ItinerisLtd/gf-sagepay/pull/256) ([codepuncher](https://github.com/codepuncher))
- fix\(confirmation\): trust the reported payment status regardless of entry age [\#255](https://github.com/ItinerisLtd/gf-sagepay/pull/255) ([codepuncher](https://github.com/codepuncher))
- style\(php\): import native functions and name the suppressed sniff [\#254](https://github.com/ItinerisLtd/gf-sagepay/pull/254) ([codepuncher](https://github.com/codepuncher))
- fix\(confirmation\): consume the confirmation token atomically after loading [\#253](https://github.com/ItinerisLtd/gf-sagepay/pull/253) ([codepuncher](https://github.com/codepuncher))
- fix\(confirmation\): stop with an error when the form cannot be loaded [\#252](https://github.com/ItinerisLtd/gf-sagepay/pull/252) ([codepuncher](https://github.com/codepuncher))
- fix\(confirmation\): reject non-string token and entry query args [\#251](https://github.com/ItinerisLtd/gf-sagepay/pull/251) ([codepuncher](https://github.com/codepuncher))
- chore\(deps\): update runtime dependencies within current majors [\#250](https://github.com/ItinerisLtd/gf-sagepay/pull/250) ([codepuncher](https://github.com/codepuncher))
- chore\(deps\): upgrade itineris-wp-coding-standards to 2.0 [\#249](https://github.com/ItinerisLtd/gf-sagepay/pull/249) ([codepuncher](https://github.com/codepuncher))
- ci\(actions\): update and pin actions to commit shas [\#248](https://github.com/ItinerisLtd/gf-sagepay/pull/248) ([codepuncher](https://github.com/codepuncher))
- chore\(php\): bump minimum php version to 8.4 [\#247](https://github.com/ItinerisLtd/gf-sagepay/pull/247) ([codepuncher](https://github.com/codepuncher))
- ci\(test\): run on pull requests instead of branch pushes [\#246](https://github.com/ItinerisLtd/gf-sagepay/pull/246) ([codepuncher](https://github.com/codepuncher))
- docs\(claude\): add CLAUDE.md [\#245](https://github.com/ItinerisLtd/gf-sagepay/pull/245) ([codepuncher](https://github.com/codepuncher))
- Add 21-day cooldown to Dependabot composer updates [\#244](https://github.com/ItinerisLtd/gf-sagepay/pull/244) ([codepuncher](https://github.com/codepuncher))

## [0.12.1](https://github.com/itinerisltd/gf-sagepay/tree/0.12.1) (2025-11-03)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.12.0...0.12.1)

**Merged pull requests:**

- fix\(bug\): PHP fatal error: Class 'GFForm' not found [\#243](https://github.com/ItinerisLtd/gf-sagepay/pull/243) ([itineris-milos](https://github.com/itineris-milos))
- Update GitHub Actions workflows to version `v4` [\#242](https://github.com/ItinerisLtd/gf-sagepay/pull/242) ([codepuncher](https://github.com/codepuncher))

## [0.12.0](https://github.com/itinerisltd/gf-sagepay/tree/0.12.0) (2024-01-10)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.11.0...0.12.0)

**Closed issues:**

- Update `omnipay/sagepay`  to v4 [\#234](https://github.com/ItinerisLtd/gf-sagepay/issues/234)

**Merged pull requests:**

- Bump version 0.12.0 [\#238](https://github.com/ItinerisLtd/gf-sagepay/pull/238) ([codepuncher](https://github.com/codepuncher))
- Replace CircleCI badge with GitHub Actions badge [\#237](https://github.com/ItinerisLtd/gf-sagepay/pull/237) ([codepuncher](https://github.com/codepuncher))
- Upgrade `omnipay/sagepay` from `3.2.0` to `4.1.1` [\#236](https://github.com/ItinerisLtd/gf-sagepay/pull/236) ([codepuncher](https://github.com/codepuncher))
- Migrate to GitHub Actions [\#235](https://github.com/ItinerisLtd/gf-sagepay/pull/235) ([codepuncher](https://github.com/codepuncher))

## [0.11.0](https://github.com/itinerisltd/gf-sagepay/tree/0.11.0) (2023-01-23)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.10.10...0.11.0)

**Merged pull requests:**

- php 8.1 [\#230](https://github.com/ItinerisLtd/gf-sagepay/pull/230) ([codepuncher](https://github.com/codepuncher))
- Upgrade to GitHub-native Dependabot [\#211](https://github.com/ItinerisLtd/gf-sagepay/pull/211) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))

## [0.10.10](https://github.com/itinerisltd/gf-sagepay/tree/0.10.10) (2021-08-03)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.10.9...0.10.10)

**Security fixes:**

- \[Security\] Bump symfony/http-foundation from 4.4.6 to 4.4.7 [\#135](https://github.com/ItinerisLtd/gf-sagepay/pull/135) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))

**Closed issues:**

- Rename for wp.org [\#114](https://github.com/ItinerisLtd/gf-sagepay/issues/114)

**Merged pull requests:**

- Fix getProperty cast [\#219](https://github.com/ItinerisLtd/gf-sagepay/pull/219) ([danlapteacru](https://github.com/danlapteacru))
- Update CircleCI config [\#206](https://github.com/ItinerisLtd/gf-sagepay/pull/206) ([tangrufus](https://github.com/tangrufus))
- Give up publishing to wp.org [\#205](https://github.com/ItinerisLtd/gf-sagepay/pull/205) ([tangrufus](https://github.com/tangrufus))
- Bump symfony/http-foundation from 4.4.16 to 4.4.17 [\#196](https://github.com/ItinerisLtd/gf-sagepay/pull/196) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump php-http/discovery from 1.12.0 to 1.13.0 [\#195](https://github.com/ItinerisLtd/gf-sagepay/pull/195) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump php-http/message from 1.9.1 to 1.10.0 [\#194](https://github.com/ItinerisLtd/gf-sagepay/pull/194) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump omnipay/common from 3.0.4 to 3.0.5 [\#193](https://github.com/ItinerisLtd/gf-sagepay/pull/193) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/http-foundation from 4.4.15 to 4.4.16 [\#191](https://github.com/ItinerisLtd/gf-sagepay/pull/191) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/polyfill-php72 from 1.18.1 to 1.20.0 [\#190](https://github.com/ItinerisLtd/gf-sagepay/pull/190) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/polyfill-mbstring from 1.18.1 to 1.20.0 [\#189](https://github.com/ItinerisLtd/gf-sagepay/pull/189) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump squizlabs/php\_codesniffer from 3.5.6 to 3.5.8 [\#186](https://github.com/ItinerisLtd/gf-sagepay/pull/186) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump php-http/message from 1.9.0 to 1.9.1 [\#185](https://github.com/ItinerisLtd/gf-sagepay/pull/185) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/http-foundation from 4.4.14 to 4.4.15 [\#183](https://github.com/ItinerisLtd/gf-sagepay/pull/183) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump clue/stream-filter from 1.4.1 to 1.5.0 [\#182](https://github.com/ItinerisLtd/gf-sagepay/pull/182) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump guzzlehttp/psr7 from 1.6.1 to 1.7.0 [\#181](https://github.com/ItinerisLtd/gf-sagepay/pull/181) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump guzzlehttp/promises from 1.3.1 to 1.4.0 [\#180](https://github.com/ItinerisLtd/gf-sagepay/pull/180) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/http-foundation from 4.4.13 to 4.4.14 [\#179](https://github.com/ItinerisLtd/gf-sagepay/pull/179) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump php-http/discovery from 1.11.0 to 1.12.0 [\#177](https://github.com/ItinerisLtd/gf-sagepay/pull/177) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump php-http/discovery from 1.10.0 to 1.11.0 [\#176](https://github.com/ItinerisLtd/gf-sagepay/pull/176) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump itinerisltd/itineris-wp-coding-standards from 0.3.5 to 0.4.0 [\#175](https://github.com/ItinerisLtd/gf-sagepay/pull/175) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump php-http/discovery from 1.9.1 to 1.10.0 [\#174](https://github.com/ItinerisLtd/gf-sagepay/pull/174) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/http-foundation from 4.4.12 to 4.4.13 [\#173](https://github.com/ItinerisLtd/gf-sagepay/pull/173) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/mime from 4.4.12 to 4.4.13 [\#172](https://github.com/ItinerisLtd/gf-sagepay/pull/172) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/http-foundation from 4.4.11 to 4.4.12 [\#171](https://github.com/ItinerisLtd/gf-sagepay/pull/171) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump php-http/message from 1.8.0 to 1.9.0 [\#169](https://github.com/ItinerisLtd/gf-sagepay/pull/169) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump squizlabs/php\_codesniffer from 3.5.5 to 3.5.6 [\#168](https://github.com/ItinerisLtd/gf-sagepay/pull/168) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/polyfill-mbstring from 1.18.0 to 1.18.1 [\#167](https://github.com/ItinerisLtd/gf-sagepay/pull/167) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/polyfill-php72 from 1.18.0 to 1.18.1 [\#166](https://github.com/ItinerisLtd/gf-sagepay/pull/166) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/http-foundation from 4.4.10 to 4.4.11 [\#165](https://github.com/ItinerisLtd/gf-sagepay/pull/165) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/mime from 4.4.10 to 4.4.11 [\#164](https://github.com/ItinerisLtd/gf-sagepay/pull/164) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/polyfill-mbstring from 1.17.1 to 1.18.0 [\#163](https://github.com/ItinerisLtd/gf-sagepay/pull/163) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/polyfill-php72 from 1.17.1 to 1.18.0 [\#162](https://github.com/ItinerisLtd/gf-sagepay/pull/162) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/polyfill-php72 from 1.17.0 to 1.17.1 [\#161](https://github.com/ItinerisLtd/gf-sagepay/pull/161) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump php-http/httplug from 2.1.0 to 2.2.0 [\#160](https://github.com/ItinerisLtd/gf-sagepay/pull/160) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump php-http/discovery from 1.9.0 to 1.9.1 [\#159](https://github.com/ItinerisLtd/gf-sagepay/pull/159) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump php-http/promise from 1.0.0 to 1.1.0 [\#158](https://github.com/ItinerisLtd/gf-sagepay/pull/158) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump php-http/discovery from 1.8.0 to 1.9.0 [\#157](https://github.com/ItinerisLtd/gf-sagepay/pull/157) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump psr/http-client from 1.0.0 to 1.0.1 [\#156](https://github.com/ItinerisLtd/gf-sagepay/pull/156) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/polyfill-mbstring from 1.17.0 to 1.17.1 [\#155](https://github.com/ItinerisLtd/gf-sagepay/pull/155) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/http-foundation from 4.4.9 to 4.4.10 [\#153](https://github.com/ItinerisLtd/gf-sagepay/pull/153) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump php-http/discovery from 1.7.4 to 1.8.0 [\#152](https://github.com/ItinerisLtd/gf-sagepay/pull/152) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump omnipay/common from 3.0.3 to 3.0.4 [\#151](https://github.com/ItinerisLtd/gf-sagepay/pull/151) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/http-foundation from 4.4.8 to 4.4.9 [\#149](https://github.com/ItinerisLtd/gf-sagepay/pull/149) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump wp-coding-standards/wpcs from 2.2.1 to 2.3.0 [\#147](https://github.com/ItinerisLtd/gf-sagepay/pull/147) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/polyfill-intl-idn from 1.16.0 to 1.17.0 [\#145](https://github.com/ItinerisLtd/gf-sagepay/pull/145) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/polyfill-intl-idn from 1.15.0 to 1.16.0 [\#143](https://github.com/ItinerisLtd/gf-sagepay/pull/143) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/http-foundation from 4.4.7 to 4.4.8 [\#140](https://github.com/ItinerisLtd/gf-sagepay/pull/140) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/mime from 4.4.7 to 4.4.8 [\#139](https://github.com/ItinerisLtd/gf-sagepay/pull/139) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump omnipay/common from 3.0.2 to 3.0.3 [\#138](https://github.com/ItinerisLtd/gf-sagepay/pull/138) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump guzzlehttp/guzzle from 6.5.2 to 6.5.3 [\#137](https://github.com/ItinerisLtd/gf-sagepay/pull/137) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump squizlabs/php\_codesniffer from 3.5.4 to 3.5.5 [\#136](https://github.com/ItinerisLtd/gf-sagepay/pull/136) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/http-foundation from 4.4.5 to 4.4.6 [\#134](https://github.com/ItinerisLtd/gf-sagepay/pull/134) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/mime from 4.4.5 to 4.4.6 [\#133](https://github.com/ItinerisLtd/gf-sagepay/pull/133) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/polyfill-intl-idn from 1.14.0 to 1.15.0 [\#131](https://github.com/ItinerisLtd/gf-sagepay/pull/131) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/polyfill-php72 from 1.14.0 to 1.15.0 [\#130](https://github.com/ItinerisLtd/gf-sagepay/pull/130) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump moneyphp/money from 3.3.0 to 3.3.1 [\#129](https://github.com/ItinerisLtd/gf-sagepay/pull/129) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/http-foundation from 4.4.4 to 4.4.5 [\#128](https://github.com/ItinerisLtd/gf-sagepay/pull/128) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/mime from 4.4.4 to 4.4.5 [\#127](https://github.com/ItinerisLtd/gf-sagepay/pull/127) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/polyfill-intl-idn from 1.13.1 to 1.14.0 [\#125](https://github.com/ItinerisLtd/gf-sagepay/pull/125) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/polyfill-php72 from 1.13.1 to 1.14.0 [\#124](https://github.com/ItinerisLtd/gf-sagepay/pull/124) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))

## [0.10.9](https://github.com/itinerisltd/gf-sagepay/tree/0.10.9) (2020-02-11)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.10.8...0.10.9)

**Merged pull requests:**

- Version bump 0.10.9 [\#123](https://github.com/ItinerisLtd/gf-sagepay/pull/123) ([tangrufus](https://github.com/tangrufus))
- Defer setting transcation id and payment amount [\#122](https://github.com/ItinerisLtd/gf-sagepay/pull/122) ([tangrufus](https://github.com/tangrufus))
- Bump wp-coding-standards/wpcs from 2.2.0 to 2.2.1 [\#121](https://github.com/ItinerisLtd/gf-sagepay/pull/121) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))

## [0.10.8](https://github.com/itinerisltd/gf-sagepay/tree/0.10.8) (2020-01-31)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.10.7...0.10.8)

**Merged pull requests:**

- Version bump 0.10.8 [\#120](https://github.com/ItinerisLtd/gf-sagepay/pull/120) ([tangrufus](https://github.com/tangrufus))
- Fix entry not found in callback [\#119](https://github.com/ItinerisLtd/gf-sagepay/pull/119) ([tangrufus](https://github.com/tangrufus))

## [0.10.7](https://github.com/itinerisltd/gf-sagepay/tree/0.10.7) (2020-01-31)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.10.6...0.10.7)

**Merged pull requests:**

- Version bump 0.10.7 [\#118](https://github.com/ItinerisLtd/gf-sagepay/pull/118) ([tangrufus](https://github.com/tangrufus))
- Bump squizlabs/php\_codesniffer from 3.5.3 to 3.5.4 [\#117](https://github.com/ItinerisLtd/gf-sagepay/pull/117) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Add more logging; Speed improvment; Ensure `exit` after confirmation [\#116](https://github.com/ItinerisLtd/gf-sagepay/pull/116) ([tangrufus](https://github.com/tangrufus))
- Composer: Lock platform to PHP v7.2; Cleanup `.gitignore` [\#115](https://github.com/ItinerisLtd/gf-sagepay/pull/115) ([tangrufus](https://github.com/tangrufus))
- Bump symfony/http-foundation from 4.4.2 to 4.4.3 [\#113](https://github.com/ItinerisLtd/gf-sagepay/pull/113) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))

## [0.10.6](https://github.com/itinerisltd/gf-sagepay/tree/0.10.6) (2020-01-17)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.10.5...0.10.6)

**Merged pull requests:**

- Version bump 0.10.6 [\#112](https://github.com/ItinerisLtd/gf-sagepay/pull/112) ([tangrufus](https://github.com/tangrufus))
- Fix entry not found in callback; composer update [\#111](https://github.com/ItinerisLtd/gf-sagepay/pull/111) ([tangrufus](https://github.com/tangrufus))
- Bump php-http/discovery from 1.7.3 to 1.7.4 [\#110](https://github.com/ItinerisLtd/gf-sagepay/pull/110) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump moneyphp/money from 3.2.1 to 3.3.0 [\#109](https://github.com/ItinerisLtd/gf-sagepay/pull/109) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump php-http/discovery from 1.7.1 to 1.7.3 [\#108](https://github.com/ItinerisLtd/gf-sagepay/pull/108) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump php-http/httplug from 2.0.0 to 2.1.0 [\#106](https://github.com/ItinerisLtd/gf-sagepay/pull/106) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump php-http/discovery from 1.7.0 to 1.7.1 [\#105](https://github.com/ItinerisLtd/gf-sagepay/pull/105) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump guzzlehttp/guzzle from 6.5.1 to 6.5.2 [\#104](https://github.com/ItinerisLtd/gf-sagepay/pull/104) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump guzzlehttp/guzzle from 6.5.0 to 6.5.1 [\#103](https://github.com/ItinerisLtd/gf-sagepay/pull/103) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump symfony/http-foundation from 4.4.1 to 4.4.2 [\#102](https://github.com/ItinerisLtd/gf-sagepay/pull/102) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump itinerisltd/itineris-wp-coding-standards from 0.3.2 to 0.3.5 [\#101](https://github.com/ItinerisLtd/gf-sagepay/pull/101) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump guzzlehttp/guzzle from 6.4.1 to 6.5.0 [\#99](https://github.com/ItinerisLtd/gf-sagepay/pull/99) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))
- Bump squizlabs/php\_codesniffer from 3.5.2 to 3.5.3 [\#98](https://github.com/ItinerisLtd/gf-sagepay/pull/98) ([dependabot-preview[bot]](https://github.com/apps/dependabot-preview))

## [0.10.5](https://github.com/itinerisltd/gf-sagepay/tree/0.10.5) (2019-12-03)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.10.4...0.10.5)

**Merged pull requests:**

- composer require omnipay/sagepay && composer require --dev itinerisltd/itineris-wp-coding-standards [\#97](https://github.com/ItinerisLtd/gf-sagepay/pull/97) ([tangrufus](https://github.com/tangrufus))
- Version bump 0.10.5 [\#96](https://github.com/ItinerisLtd/gf-sagepay/pull/96) ([tangrufus](https://github.com/tangrufus))
- Add dependabot [\#94](https://github.com/ItinerisLtd/gf-sagepay/pull/94) ([tangrufus](https://github.com/tangrufus))
- composer update [\#93](https://github.com/ItinerisLtd/gf-sagepay/pull/93) ([tangrufus](https://github.com/tangrufus))
- README.md: Fix `Minimum Requirements` [\#92](https://github.com/ItinerisLtd/gf-sagepay/pull/92) ([tangrufus](https://github.com/tangrufus))
- Plugin Header: Add `Requires at least` and `Requires PHP` [\#91](https://github.com/ItinerisLtd/gf-sagepay/pull/91) ([tangrufus](https://github.com/tangrufus))

## [0.10.4](https://github.com/itinerisltd/gf-sagepay/tree/0.10.4) (2019-10-20)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.10.3...0.10.4)

**Merged pull requests:**

- Version bump 0.10.4 [\#90](https://github.com/ItinerisLtd/gf-sagepay/pull/90) ([tangrufus](https://github.com/tangrufus))
- Update `Plugin URI` to GitHub repo URL [\#89](https://github.com/ItinerisLtd/gf-sagepay/pull/89) ([tangrufus](https://github.com/tangrufus))

## [0.10.3](https://github.com/itinerisltd/gf-sagepay/tree/0.10.3) (2019-10-20)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.10.2...0.10.3)

**Closed issues:**

- fix circleci publish-to-github-releases [\#84](https://github.com/ItinerisLtd/gf-sagepay/issues/84)

**Merged pull requests:**

- Version bump 0.10.3 [\#88](https://github.com/ItinerisLtd/gf-sagepay/pull/88) ([tangrufus](https://github.com/tangrufus))
- Composer: Do not archive `tests` [\#87](https://github.com/ItinerisLtd/gf-sagepay/pull/87) ([tangrufus](https://github.com/tangrufus))

## [0.10.2](https://github.com/itinerisltd/gf-sagepay/tree/0.10.2) (2019-10-20)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.10.1...0.10.2)

**Closed issues:**

- fix circleci publish-to-github-releases [\#84](https://github.com/ItinerisLtd/gf-sagepay/issues/84)

**Merged pull requests:**

- Version bump 0.10.2 [\#86](https://github.com/ItinerisLtd/gf-sagepay/pull/86) ([tangrufus](https://github.com/tangrufus))
- Prepare for wp.org review [\#85](https://github.com/ItinerisLtd/gf-sagepay/pull/85) ([tangrufus](https://github.com/tangrufus))

## [0.10.1](https://github.com/itinerisltd/gf-sagepay/tree/0.10.1) (2019-10-16)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.10.0...0.10.1)

**Merged pull requests:**

- Version bump 0.10.1 [\#83](https://github.com/ItinerisLtd/gf-sagepay/pull/83) ([tangrufus](https://github.com/tangrufus))
- Composer: Fix `release:build` [\#82](https://github.com/ItinerisLtd/gf-sagepay/pull/82) ([tangrufus](https://github.com/tangrufus))

## [0.10.0](https://github.com/itinerisltd/gf-sagepay/tree/0.10.0) (2019-10-16)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.9.6...0.10.0)

**Merged pull requests:**

- Version bump 0.10.0 [\#81](https://github.com/ItinerisLtd/gf-sagepay/pull/81) ([tangrufus](https://github.com/tangrufus))
- Add support for `vendorData` \(reference\) field [\#80](https://github.com/ItinerisLtd/gf-sagepay/pull/80) ([tangrufus](https://github.com/tangrufus))
- Update code standards [\#79](https://github.com/ItinerisLtd/gf-sagepay/pull/79) ([tangrufus](https://github.com/tangrufus))
- Remove Preflight [\#78](https://github.com/ItinerisLtd/gf-sagepay/pull/78) ([tangrufus](https://github.com/tangrufus))
- Update readme [\#77](https://github.com/ItinerisLtd/gf-sagepay/pull/77) ([tangrufus](https://github.com/tangrufus))
- remove `.prettyci.composer.json` [\#76](https://github.com/ItinerisLtd/gf-sagepay/pull/76) ([tangrufus](https://github.com/tangrufus))
- Git: Track `composer.json` [\#75](https://github.com/ItinerisLtd/gf-sagepay/pull/75) ([tangrufus](https://github.com/tangrufus))
- Replace PrettyCI with CircleCI [\#74](https://github.com/ItinerisLtd/gf-sagepay/pull/74) ([tangrufus](https://github.com/tangrufus))
- Bump minimum gravity forms version to 2.4.14.4; Bump minimum PHP version to 7.2 [\#73](https://github.com/ItinerisLtd/gf-sagepay/pull/73) ([tangrufus](https://github.com/tangrufus))
- Use Itineris WP Coding Standards [\#72](https://github.com/ItinerisLtd/gf-sagepay/pull/72) ([tangrufus](https://github.com/tangrufus))

## [0.9.6](https://github.com/itinerisltd/gf-sagepay/tree/0.9.6) (2018-10-18)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.9.5...0.9.6)

**Merged pull requests:**

- Version bump 0.9.6 [\#71](https://github.com/ItinerisLtd/gf-sagepay/pull/71) ([tangrufus](https://github.com/tangrufus))
- Restore original `$_FILES` after symfony/http-foundation is initialized [\#70](https://github.com/ItinerisLtd/gf-sagepay/pull/70) ([tangrufus](https://github.com/tangrufus))

## [0.9.5](https://github.com/itinerisltd/gf-sagepay/tree/0.9.5) (2018-10-18)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.9.4...0.9.5)

**Merged pull requests:**

- Version bump 0.9.5 [\#69](https://github.com/ItinerisLtd/gf-sagepay/pull/69) ([tangrufus](https://github.com/tangrufus))
- Empty `$_FILES` to prevent conflicts with `symfony/http-foundation` [\#68](https://github.com/ItinerisLtd/gf-sagepay/pull/68) ([tangrufus](https://github.com/tangrufus))

## [0.9.4](https://github.com/itinerisltd/gf-sagepay/tree/0.9.4) (2018-10-16)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.9.3...0.9.4)

**Merged pull requests:**

- Version bump 0.9.4 [\#67](https://github.com/ItinerisLtd/gf-sagepay/pull/67) ([tangrufus](https://github.com/tangrufus))
- Add extra query param to confirmation URL [\#66](https://github.com/ItinerisLtd/gf-sagepay/pull/66) ([tangrufus](https://github.com/tangrufus))

## [0.9.3](https://github.com/itinerisltd/gf-sagepay/tree/0.9.3) (2018-10-11)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.9.2...0.9.3)

**Merged pull requests:**

- Version bump 0.9.3 [\#65](https://github.com/ItinerisLtd/gf-sagepay/pull/65) ([tangrufus](https://github.com/tangrufus))
- Extra check for timeout payment [\#64](https://github.com/ItinerisLtd/gf-sagepay/pull/64) ([tangrufus](https://github.com/tangrufus))
- Update `wp-coding-standards/wpcs` to v1.1.0 [\#61](https://github.com/ItinerisLtd/gf-sagepay/pull/61) ([tangrufus](https://github.com/tangrufus))

## [0.9.2](https://github.com/itinerisltd/gf-sagepay/tree/0.9.2) (2018-09-09)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.9.1...0.9.2)

**Closed issues:**

- Payments failing [\#54](https://github.com/ItinerisLtd/gf-sagepay/issues/54)
-  Use new packagist.org auto-update mechanism [\#49](https://github.com/ItinerisLtd/gf-sagepay/issues/49)

**Merged pull requests:**

- Version bump 0.9.2 [\#60](https://github.com/ItinerisLtd/gf-sagepay/pull/60) ([tangrufus](https://github.com/tangrufus))
- CallbackHandler: Remove useless validation [\#59](https://github.com/ItinerisLtd/gf-sagepay/pull/59) ([tangrufus](https://github.com/tangrufus))

## [0.9.1](https://github.com/itinerisltd/gf-sagepay/tree/0.9.1) (2018-09-05)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.9.0...0.9.1)

**Merged pull requests:**

- Version bump 0.9.1 [\#57](https://github.com/ItinerisLtd/gf-sagepay/pull/57) ([tangrufus](https://github.com/tangrufus))
- Update code standards; Add PrettryCI config [\#56](https://github.com/ItinerisLtd/gf-sagepay/pull/56) ([tangrufus](https://github.com/tangrufus))
- Better logging [\#55](https://github.com/ItinerisLtd/gf-sagepay/pull/55) ([tangrufus](https://github.com/tangrufus))

## [0.9.0](https://github.com/itinerisltd/gf-sagepay/tree/0.9.0) (2018-08-22)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.8.0...0.9.0)

**Closed issues:**

- Fix typo: `Entry::makeAsProcessing` [\#45](https://github.com/ItinerisLtd/gf-sagepay/issues/45)

**Merged pull requests:**

- Version bump 0.9.0 [\#53](https://github.com/ItinerisLtd/gf-sagepay/pull/53) ([tangrufus](https://github.com/tangrufus))
- :bike:Bikeshedding for open sourcing [\#52](https://github.com/ItinerisLtd/gf-sagepay/pull/52) ([tangrufus](https://github.com/tangrufus))
- Create LICENSE [\#51](https://github.com/ItinerisLtd/gf-sagepay/pull/51) ([tangrufus](https://github.com/tangrufus))
- Rename: `Entry::{make --> mark}AsProcessing` [\#50](https://github.com/ItinerisLtd/gf-sagepay/pull/50) ([tangrufus](https://github.com/tangrufus))

## [0.8.0](https://github.com/itinerisltd/gf-sagepay/tree/0.8.0) (2018-08-20)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.7.0...0.8.0)

**Merged pull requests:**

- Version bump 0.8.0 [\#48](https://github.com/ItinerisLtd/gf-sagepay/pull/48) ([tangrufus](https://github.com/tangrufus))
- Add preflight checker; Fix incorrect fallback to test mode [\#47](https://github.com/ItinerisLtd/gf-sagepay/pull/47) ([tangrufus](https://github.com/tangrufus))
- Bikeshedding [\#46](https://github.com/ItinerisLtd/gf-sagepay/pull/46) ([tangrufus](https://github.com/tangrufus))

## [0.7.0](https://github.com/itinerisltd/gf-sagepay/tree/0.7.0) (2018-07-23)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.6.0...0.7.0)

**Merged pull requests:**

- Version bump 0.7.0 [\#44](https://github.com/ItinerisLtd/gf-sagepay/pull/44) ([tangrufus](https://github.com/tangrufus))
- Revert "New Feature: Copy billing address to shipping address" [\#43](https://github.com/ItinerisLtd/gf-sagepay/pull/43) ([tangrufus](https://github.com/tangrufus))

## [0.6.0](https://github.com/itinerisltd/gf-sagepay/tree/0.6.0) (2018-05-28)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.5.3...0.6.0)

**Implemented enhancements:**

- Conditional user payment details [\#40](https://github.com/ItinerisLtd/gf-sagepay/issues/40)

**Merged pull requests:**

- Version bump 0.6.0 [\#42](https://github.com/ItinerisLtd/gf-sagepay/pull/42) ([tangrufus](https://github.com/tangrufus))
- New Feature: Copy billing address to shipping address [\#41](https://github.com/ItinerisLtd/gf-sagepay/pull/41) ([tangrufus](https://github.com/tangrufus))

## [0.5.3](https://github.com/itinerisltd/gf-sagepay/tree/0.5.3) (2018-05-17)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.5.2...0.5.3)

**Merged pull requests:**

- Version bump 0.5.3 [\#39](https://github.com/ItinerisLtd/gf-sagepay/pull/39) ([tangrufus](https://github.com/tangrufus))
- Update omnipay/sagepay to ^3.0 [\#38](https://github.com/ItinerisLtd/gf-sagepay/pull/38) ([tangrufus](https://github.com/tangrufus))

## [0.5.2](https://github.com/itinerisltd/gf-sagepay/tree/0.5.2) (2018-05-16)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.5.1...0.5.2)

**Merged pull requests:**

- Version bump 0.5.2 [\#37](https://github.com/ItinerisLtd/gf-sagepay/pull/37) ([tangrufus](https://github.com/tangrufus))
- `wp_die` when redirect URL retrievals fail [\#36](https://github.com/ItinerisLtd/gf-sagepay/pull/36) ([tangrufus](https://github.com/tangrufus))

## [0.5.1](https://github.com/itinerisltd/gf-sagepay/tree/0.5.1) (2018-04-30)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.5.0...0.5.1)

**Merged pull requests:**

- Version bump 0.5.1 [\#35](https://github.com/ItinerisLtd/gf-sagepay/pull/35) ([tangrufus](https://github.com/tangrufus))
- Lower Gravity Forms requirment to v2.2.6.1 [\#34](https://github.com/ItinerisLtd/gf-sagepay/pull/34) ([tangrufus](https://github.com/tangrufus))
- Apply coding style [\#33](https://github.com/ItinerisLtd/gf-sagepay/pull/33) ([tangrufus](https://github.com/tangrufus))

## [0.5.0](https://github.com/itinerisltd/gf-sagepay/tree/0.5.0) (2018-04-29)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.4.1...0.5.0)

**Merged pull requests:**

- Version bump 0.5.0 [\#32](https://github.com/ItinerisLtd/gf-sagepay/pull/32) ([tangrufus](https://github.com/tangrufus))
- Fix: `AddOn::$_path` should point to `gf-sagepay.php` [\#31](https://github.com/ItinerisLtd/gf-sagepay/pull/31) ([tangrufus](https://github.com/tangrufus))
- New feature: Cancel url [\#30](https://github.com/ItinerisLtd/gf-sagepay/pull/30) ([tangrufus](https://github.com/tangrufus))
- Lower PHP requirement to ^7.1; Bump GF requirement to v2.3.0.4 [\#29](https://github.com/ItinerisLtd/gf-sagepay/pull/29) ([tangrufus](https://github.com/tangrufus))
- Add capabilities [\#28](https://github.com/ItinerisLtd/gf-sagepay/pull/28) ([tangrufus](https://github.com/tangrufus))

## [0.4.1](https://github.com/itinerisltd/gf-sagepay/tree/0.4.1) (2018-04-28)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.4.0...0.4.1)

**Merged pull requests:**

- Version bump 0.4.1 [\#27](https://github.com/ItinerisLtd/gf-sagepay/pull/27) ([tangrufus](https://github.com/tangrufus))

## [0.4.0](https://github.com/itinerisltd/gf-sagepay/tree/0.4.0) (2018-04-20)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.3.0...0.4.0)

**Closed issues:**

- \(feature\) default settings  [\#17](https://github.com/ItinerisLtd/gf-sagepay/issues/17)

**Merged pull requests:**

- Version bump 0.4.0 [\#26](https://github.com/ItinerisLtd/gf-sagepay/pull/26) ([tangrufus](https://github.com/tangrufus))
- Expose `ConfirmationHandler::buildUrlFor` as a public api [\#25](https://github.com/ItinerisLtd/gf-sagepay/pull/25) ([tangrufus](https://github.com/tangrufus))
- Feed Settings Form: Use `select_custom` as vendor code field type [\#24](https://github.com/ItinerisLtd/gf-sagepay/pull/24) ([tangrufus](https://github.com/tangrufus))
- Verify token from query string before passing onto confirmation [\#23](https://github.com/ItinerisLtd/gf-sagepay/pull/23) ([tangrufus](https://github.com/tangrufus))
- Readme: Clarify page confirmation is supported [\#22](https://github.com/ItinerisLtd/gf-sagepay/pull/22) ([tangrufus](https://github.com/tangrufus))

## [0.3.0](https://github.com/itinerisltd/gf-sagepay/tree/0.3.0) (2018-04-18)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.2.0...0.3.0)

**Closed issues:**

- \(feature\) Gift Aid functionality [\#13](https://github.com/ItinerisLtd/gf-sagepay/issues/13)
- \(feature\) "Next URL" needs to make use of Gravity Forms Confirmations [\#12](https://github.com/ItinerisLtd/gf-sagepay/issues/12)

**Merged pull requests:**

- Version bump 0.3.0 [\#21](https://github.com/ItinerisLtd/gf-sagepay/pull/21) ([tangrufus](https://github.com/tangrufus))
- Use Gravity Forms redirection confirmation as `nextUrl` [\#20](https://github.com/ItinerisLtd/gf-sagepay/pull/20) ([tangrufus](https://github.com/tangrufus))
- Refactor: Make `CallbackHandler` more like `omnipay-sagepay` readme example [\#19](https://github.com/ItinerisLtd/gf-sagepay/pull/19) ([tangrufus](https://github.com/tangrufus))
- Use `GFFormsModel::get_uuid` instead of ramsey/uuid package [\#18](https://github.com/ItinerisLtd/gf-sagepay/pull/18) ([tangrufus](https://github.com/tangrufus))
- Readme: Fix install instruction [\#16](https://github.com/ItinerisLtd/gf-sagepay/pull/16) ([tangrufus](https://github.com/tangrufus))

## [0.2.0](https://github.com/itinerisltd/gf-sagepay/tree/0.2.0) (2018-04-16)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.1.1...0.2.0)

**Closed issues:**

- \(feature\) Gift Aid functionality [\#13](https://github.com/ItinerisLtd/gf-sagepay/issues/13)

**Merged pull requests:**

- Version bump 0.2.0 [\#15](https://github.com/ItinerisLtd/gf-sagepay/pull/15) ([tangrufus](https://github.com/tangrufus))
- Add gift aid support [\#14](https://github.com/ItinerisLtd/gf-sagepay/pull/14) ([tangrufus](https://github.com/tangrufus))

## [0.1.1](https://github.com/itinerisltd/gf-sagepay/tree/0.1.1) (2018-04-16)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/0.1.0...0.1.1)

**Merged pull requests:**

- Version bump 0.1.1 [\#11](https://github.com/ItinerisLtd/gf-sagepay/pull/11) ([tangrufus](https://github.com/tangrufus))
- Composer: Add type `wordpress-plugin` [\#10](https://github.com/ItinerisLtd/gf-sagepay/pull/10) ([tangrufus](https://github.com/tangrufus))

## [0.1.0](https://github.com/itinerisltd/gf-sagepay/tree/0.1.0) (2018-04-16)

[Full Changelog](https://github.com/itinerisltd/gf-sagepay/compare/ed95c29393e7fe0af1fc66a540070dacf7917807...0.1.0)

**Merged pull requests:**

- Version bump 0.1.0 [\#9](https://github.com/ItinerisLtd/gf-sagepay/pull/9) ([tangrufus](https://github.com/tangrufus))
- Apply coding style [\#8](https://github.com/ItinerisLtd/gf-sagepay/pull/8) ([tangrufus](https://github.com/tangrufus))
- Bug fixes [\#7](https://github.com/ItinerisLtd/gf-sagepay/pull/7) ([tangrufus](https://github.com/tangrufus))
- Add notification support [\#6](https://github.com/ItinerisLtd/gf-sagepay/pull/6) ([tangrufus](https://github.com/tangrufus))
- Refactor: Extract `NotificationHandler` [\#5](https://github.com/ItinerisLtd/gf-sagepay/pull/5) ([tangrufus](https://github.com/tangrufus))
- Refactor: Extract `RedirectUrlFactory` [\#4](https://github.com/ItinerisLtd/gf-sagepay/pull/4) ([tangrufus](https://github.com/tangrufus))
- Readme: Fix typo [\#3](https://github.com/ItinerisLtd/gf-sagepay/pull/3) ([tangrufus](https://github.com/tangrufus))
- Refactor: Extract `FeedSettingsFields` and `MinimumRequirements` [\#2](https://github.com/ItinerisLtd/gf-sagepay/pull/2) ([tangrufus](https://github.com/tangrufus))
- First working draft [\#1](https://github.com/ItinerisLtd/gf-sagepay/pull/1) ([tangrufus](https://github.com/tangrufus))
