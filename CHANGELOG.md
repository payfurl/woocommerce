# Changelog

All notable changes to the PayFURL Extension for WooCommerce (`payfurl-extension-for-woocommerce` on WordPress.org)
are listed here, newest first, in the [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) format. Versions
follow [Semantic Versioning](https://semver.org/) from 2.0.0: a browser SDK update is a patch, a new feature a minor
version, a breaking change a major one. The release workflow publishes each version's section as its GitHub release
notes; `readme.txt`'s `== Changelog ==` is WordPress.org's shorter copy.

## [Unreleased]

## [2.0.0] - 2026-11-07

### Changed

- **Breaking:** requires WordPress 6.7, WooCommerce 9.6 and PHP 8.2 or later (the payFURL PHP SDK 3 it ships with
  requires PHP 8.2). Update them first, then the plugin. WordPress.org doesn't offer this version to a site on an
  older PHP, which keeps 1.x.
- Ships the payFURL PHP SDK 3 (`payfurl/phpsdk`), with an SDK client built from the settings instead of global
  settings set on every call.
- The checkout's payment methods list PayPal for the browser SDK the checkout loads: the plugin tells payFURL that
  version, where SDK 2 sent a fixed `4.5.7`.
- **Breaking:** the payment methods are offered on the Checkout block only, WooCommerce's default checkout since
  8.3. They are no longer listed on the classic (shortcode) checkout, the "pay for order" page or My Account's "Add
  payment method", where their forms never showed.
- Every payment is checked with payFURL before the order is marked paid. The charge must have succeeded, for the
  order's total and currency, and not have paid another order. Before, PayPal, Google Pay, Apple Pay and the
  checkout methods marked an order paid for any charge ID the browser sent, including a failed one, another
  order's or a made-up one.
- Payments still pending when the order is placed (PayID, PayTo) put the order **On hold**, unpaid, until a payFURL
  webhook or the **Check the payFURL payment** order action completes or cancels it.
- A saved card can only be charged for the logged-in customer who saved it.
- The charge reference is `Order #<order number>` (was the order ID), and order items carry what was paid for one
  after discounts, and its tax, rather than the product's current price.
- PayPal, Google Pay, Apple Pay and the checkout methods charge the cart's current total (a coupon or shipping
  change after the form loaded used to be ignored), and submit the checkout themselves instead of clicking a hidden
  Place Order button.
- The shopper's details reach the payment forms when they change, not only when the form first loads. The email
  goes to Click to Pay only once it is complete, a second after the shopper stops typing, so each email is looked
  up once.
- Each payment method has its own name on orders and emails; 1.0 used the card method's title for every method.
- Browser SDK v4.10.0 ([SDK changelog](https://docs.payfurl.com/client_sdk/changelog)).
- Settings, saved cards and orders from 1.0 carry over: the gateway IDs, the `woocommerce_payfurl_settings` option,
  the `PayfurlTransactionId` order meta and the saved cards' token type are unchanged.

### Added

- Refunds from the order in the admin, full or partial, for every payFURL payment method, 1.0 orders included.
- **Webhook signing key** setting and a webhook endpoint (`https://<your store>/?wc-api=payfurl`) that settles
  pending payments.
- **Check the payFURL payment** order action for orders on hold.
- High-Performance Order Storage (HPOS) compatibility.
- The `payfurl_sdk_url` filter, the browser SDK's URL.

### Fixed

- Paying with the saved card the checkout selects when it loads works: 1.0 read only the key the Checkout block
  sends when the shopper picks a card, and asked for card details.
- Checkout methods payFURL offers but the plugin has no payment method for (such as Zip) are no longer shown,
  where they could not be paid.
- Unticked **Enable Google Pay** and **Enable Apple Pay** no longer show the wallets.
- **Debug** works, and no longer sets the API timeout.
- Customers no longer see a "SavedTokenHandler" placeholder for a saved card.
- PHP 8 warnings when a payment failed, and errors for an order whose product was deleted.
- The checkout no longer logs its settings to the browser console.

### Removed

- The unused `WC_Payment_Token_Payfurl` class. Saved cards were always WooCommerce's card tokens.
- `build/` and `vendor/` from the repository: `just build` makes them, and the release zip has them.

## [1.0.0] - 2024-10-29

First release on WordPress.org.

[Unreleased]: https://github.com/payfurl/woocommerce/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/payfurl/woocommerce/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/payfurl/woocommerce/releases/tag/v1.0.0
