<?php
/**
 * Plugin Name:          PayFURL Extension for WooCommerce
 * Plugin URI:           https://github.com/payfurl/woocommerce
 * Description:          Take payments in WooCommerce's checkout through payFURL: cards with Click to Pay, PayPal, Google Pay, Apple Pay, PayTo, PayID and more, routed to your payment gateways.
 * Version:              2.0.0
 * Author:               PayFURL
 * Author URI:           https://payfurl.com/
 * Text Domain:          payfurl-extension-for-woocommerce
 * Requires at least:    6.7
 * Requires PHP:         8.2
 * Requires Plugins:     woocommerce
 * WC requires at least: 9.6
 * WC tested up to:      11.2
 * License:              GPL-3.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'PAYFURL_VERSION', '2.0.0' );
define( 'PAYFURL_PLUGIN_FILE', __FILE__ );
// The browser SDK, a production release: .github/workflows/sdk-bump.yml updates it. payFURL's e2e store points
// the `payfurl_sdk_url` filter at the SDK built from source.
define( 'PAYFURL_SDK_URL', 'https://assets.payfurl.com/v4.10.0/js/payfurl.js' );

require_once __DIR__ . '/vendor/autoload.php';

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}
		require_once __DIR__ . '/includes/class-payfurl-sdk.php';
		require_once __DIR__ . '/includes/class-wc-payfurl.php';
		require_once __DIR__ . '/includes/class-wc-payfurl-card.php';
		require_once __DIR__ . '/includes/class-wc-payfurl-checkout.php';
		require_once __DIR__ . '/includes/class-wc-payfurl-paypal.php';
		require_once __DIR__ . '/includes/class-wc-payfurl-payto.php';
		require_once __DIR__ . '/includes/class-wc-payfurl-googlepay.php';
		require_once __DIR__ . '/includes/class-wc-payfurl-applepay.php';
		require_once __DIR__ . '/includes/class-wc-payfurl-shift.php';
		require_once __DIR__ . '/includes/class-wc-payfurl-optty.php';
		require_once __DIR__ . '/includes/class-wc-payfurl-pesapal.php';
		require_once __DIR__ . '/includes/class-wc-payfurl-paybyaccount.php';
		require_once __DIR__ . '/includes/class-wc-payfurl-payid.php';
		require_once __DIR__ . '/includes/class-wc-payfurl-payglocal.php';
		require_once __DIR__ . '/includes/class-wc-payfurl-upi.php';
		require_once __DIR__ . '/includes/class-wc-payfurl-webhook.php';
	}
);

add_filter(
	'woocommerce_payment_gateways',
	function ( $gateways ) {
		// The settings gateway (never offered at checkout) and the payment methods, everywhere: WooCommerce's
		// Payments settings are built in a REST request, and the admin finds an order's gateway to refund it.
		return array_merge( $gateways, array( WC_Payfurl::class ), WC_Payfurl::PAYMENT_METHODS );
	}
);

add_action(
	'woocommerce_blocks_loaded',
	function () {
		require_once __DIR__ . '/includes/payfurl-blocks-integration.php';
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function ( $registry ) {
				$registry->register( new Payfurl_Blocks_Integration() );
			}
		);
	}
);

add_action( 'woocommerce_api_payfurl', array( 'WC_Payfurl_Webhook', 'handle' ) );

// "Check the payFURL payment" on an order on hold: the webhook's update, run by hand.
add_filter(
	'woocommerce_order_actions',
	function ( $actions, $order = null ) {
		if ( $order instanceof WC_Order && $order->has_status( 'on-hold' ) && $order->get_meta( WC_Payfurl::META_CHARGE_ID ) ) {
			$actions['payfurl_check_payment'] = __( 'Check the payFURL payment', 'payfurl-extension-for-woocommerce' );
		}
		return $actions;
	},
	10,
	2
);
add_action(
	'woocommerce_order_action_payfurl_check_payment',
	function ( $order ) {
		$gateway = wc_get_payment_gateway_by_order( $order );
		if ( $gateway instanceof WC_Payfurl && 'error' === $gateway->update_from_charge( $order ) ) {
			$order->add_order_note( __( 'The payFURL charge could not be fetched: see WooCommerce > Status > Logs (payfurl).', 'payfurl-extension-for-woocommerce' ) );
		}
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( array $links ) {
		$url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=payfurl' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'payfurl-extension-for-woocommerce' ) . '</a>' );
		return $links;
	}
);
