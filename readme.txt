=== PayFURL Extension for WooCommerce ===
Contributors: davidatpayfurldotcom
Tags: woocommerce, payments, payment gateway, paypal, payto
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 2.0.2
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Take cards, PayPal, Google Pay, Apple Pay, PayTo, PayID and more in WooCommerce's checkout, routed by payFURL to your payment gateways.

== Description ==

payFURL is a payment orchestration platform: you connect your payment gateways to payFURL once, and payFURL routes each payment to them. This plugin adds payFURL's payment methods to WooCommerce's block checkout.

The checkout offers the methods your payFURL account has a gateway for, for the cart's currency and total:

* **Cards**, with Mastercard Click to Pay. payFURL picks the gateway (least-cost routing). Logged-in customers can save a card and pay with it next time.
* **PayPal**.
* **Google Pay** and **Apple Pay** (Safari), when you turn them on in the settings.
* **PayTo**: the shopper approves a payment agreement in their banking app.
* **PayID** (Azupay), **Shift**, **Optty**, **Pesapal**, **Pay by Account**, **PayGLocal** and **UPI**: the shopper completes the payment in the method's own window.

Every payment is confirmed with payFURL on your server before the order is marked paid: the charge must have succeeded, for the order's total and currency, and not have paid another order. A payment that is still pending (PayID, PayTo) puts the order **On hold** until it settles.

Refunds, full or partial, are made from the order in the WordPress admin and sent to the gateway through payFURL.

= Requirements =

* WordPress 6.7 or later, PHP 8.2 or later.
* WooCommerce 9.6 or later, with the **Checkout block** on the checkout page (WooCommerce's default since 8.3). The payment methods are not offered on the classic (shortcode) checkout, the "pay for order" page or My Account's "Add payment method".
* A payFURL account with at least one payment gateway connected: [app.payfurl.com](https://app.payfurl.com/).

== Installation ==

1. Install and activate the plugin from **Plugins > Add New** (search for "payFURL"), or upload it to `/wp-content/plugins/payfurl-extension-for-woocommerce`.
2. In the payFURL portal, copy a public key and a secret key from the **Account** page (Public Keys, Secret Keys).
3. In WordPress, go to **WooCommerce > Settings > Payments > payFURL**. Tick **Enable payFURL**, pick the **Environment** (Sandbox to test, Production to take payments) and paste that environment's keys (the secret key goes in **Private Key**). Save.
4. For PayID, PayTo and other methods that can be pending: on the portal's **Webhooks** page, add a transaction webhook for the URL shown under **Webhook signing key** (`https://<your store>/?wc-api=payfurl`), and paste the **Webhook Secret Key** from the portal's **Account** page into **Webhook signing key**. Orders on hold are then completed or cancelled when their payment settles.
5. Place a test order in Sandbox, then switch to Production.

== Frequently Asked Questions ==

= Which payment methods will my shoppers see? =

The ones your payFURL account has an active gateway for, for the cart's currency and total, as payFURL reports them. Google Pay and Apple Pay also need their checkbox in the settings. To offer another method, connect its gateway in the payFURL portal.

= Why is an order "On hold"? =

Its payment was still pending when the order was placed, as PayID and PayTo payments can be. The order is completed when payFURL tells your store the payment succeeded (the webhook, see Installation step 4), or cancelled if it failed. To check one yourself, open the order and choose **Check the payFURL payment** under **Order actions**.

= Why doesn't Apple Pay show? =

Apple Pay shows in Safari only, and only when:

* Apple Pay is turned on for your payFURL **account** (portal **Settings > Apple Pay**) and on the gateway connection. The account also needs a legal name, website and country in **Account Settings**.
* **Enable Apple Pay** is ticked in the plugin's settings.
* Your domain is verified with Apple. For Westpac PayWay, Westpac registers your domain. For other gateways, host payFURL's verification file at `/.well-known/apple-developer-merchantid-domain-association` on your store and add the domain in the portal.

= How do I refund an order? =

Open the order, click **Refund**, enter the amount and click **Refund … via payFURL**. Orders paid with version 1.0 can be refunded too.

= Where are the logs? =

Errors are always logged, and with **Debug** ticked every payFURL call is too: **WooCommerce > Status > Logs**, source `payfurl`. Debug also turns on the payment form's browser console log.

= Does it work with High-Performance Order Storage (HPOS)? =

Yes.

== External services ==

This plugin sends payments to payFURL, a payment orchestration service run by PayFURL Pty Ltd, which forwards them to the payment gateways the store has connected.

* **payFURL's browser SDK**, loaded from `https://assets.payfurl.com` on the checkout page. It shows the payment forms and buttons. The card form runs in an iframe served by payFURL: card details go from the shopper's browser to payFURL, never to your store. The SDK also sends the shopper's name, email, phone, billing address and the cart's items and totals to payFURL, to create the payment and for fraud checks and 3-D Secure.
* **payFURL's API** (`https://api.payfurl.com`, or `https://sandbox-api.payfurl.com` in Sandbox), called from your store's server when an order is placed, refunded or checked: the order's number, items and totals, and the customer's name, email, phone and billing address.
* Through payFURL, the payment methods' own services: Mastercard Click to Pay, PayPal, Google Pay, Apple Pay and the checkout methods (PayID, Shift, Optty, Pesapal, Pay by Account, PayGLocal, UPI), when the shopper uses them.

payFURL's [privacy policy](https://payfurl.com/privacy-policy/). Your use of payFURL is governed by your payFURL merchant agreement; see [payfurl.com](https://payfurl.com/) and the [documentation](https://docs.payfurl.com/).

== Changelog ==

= 2.0.2 - 2026-11-12 =

* Browser SDK updated from v4.11.0 to v4.11.1.

= 2.0.1 - 2026-11-10 =

* Browser SDK updated from v4.10.0 to v4.11.0.

= 2.0.0 - 2026-11-07 =

**Breaking:** requires WordPress 6.7, WooCommerce 9.6 and PHP 8.2 or later. The payment methods are offered on the Checkout block only.

* Every payment is confirmed with payFURL before the order is marked paid. Before, an order was marked paid for any payment ID the browser sent, including a failed one or another order's.
* Pending payments (PayID, PayTo) put the order On hold until a payFURL webhook or the new **Check the payFURL payment** order action settles it. New **Webhook signing key** setting.
* Refunds from the admin, for orders paid with any payFURL method, 1.0 orders included.
* A saved card can only be used by the customer who saved it.
* Ships the payFURL PHP SDK 3.
* High-Performance Order Storage (HPOS) compatible.
* PayPal, Google Pay, Apple Pay and the checkout methods charge the cart's current total, and submit the checkout themselves.
* Each payment method's own name on orders and emails (1.0 showed the card's title for all).
* Checkout methods payFURL offers but the plugin can't take (such as Zip) are no longer shown.
* The charge reference is the order number (`Order #<number>`), and items carry what was paid after discounts.
* Browser SDK v4.10.0.
* Fixed: paying with the saved card the checkout selects when it loads, the Debug setting, the Google Pay and Apple Pay checkboxes (unticked showed them anyway), a "SavedTokenHandler" placeholder shown to customers, PHP 8 warnings.

= 1.0.0 =

* First release.

== Upgrade Notice ==

= 2.0.0 =

Every payment is now confirmed with payFURL on your server. Requires WordPress 6.7, WooCommerce 9.6, PHP 8.2 and the Checkout block. Your settings, saved cards and orders carry over. For PayID and PayTo, add the webhook (see Installation).
