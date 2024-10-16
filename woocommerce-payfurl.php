<?php
/**
 * Plugin Name:         PayFURL Extension for WooCommerce
 * Plugin URI:          https://github.com/payfurl/woocommerce
 * Description:         PayFURL payment extension for WooCommerce.
 * Version:             1.0.0
 * Author:              PayFURL
 * Author URI:          https://payfurl.com/
 * Requires Plugins:    woocommerce
 * License:             GPL-3.0-or-later
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package         create-block
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$loader = require_once(__DIR__ . '/vendor/autoload.php');
$loader->addPsr4('payFURL\\', __DIR__ . '/vendor/payfurl/sdk/src');

add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

add_action('woocommerce_blocks_loaded', function () {
    require_once __DIR__ . '/includes/payfurl-blocks-integration.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function ($integration_registry) {
            $integration_registry->register(new Payfurl_Blocks_Integration());
        }
    );
});


add_filter('woocommerce_payment_gateways', function ($gateways) {
    if (is_admin()) {
        $gateways[] = WC_Payfurl::class;
    }
    else {
        $gateways[] = WC_Payfurl_Card::class;
        $gateways[] = WC_Payfurl_Paypal::class;
        $gateways[] = WC_Payfurl_Checkout::class;
        $gateways[] = WC_Payfurl_PayTo::class;
        $gateways[] = WC_Payfurl_GooglePay::class;
        $gateways[] = WC_Payfurl_ApplePay::class;
        $gateways[] = WC_Payfurl_Shift::class;
        $gateways[] = WC_Payfurl_Optty::class;
        $gateways[] = WC_Payfurl_Pesapal::class;
        $gateways[] = WC_Payfurl_PayByAccount::class;
        $gateways[] = WC_Payfurl_PayId::class;
        $gateways[] = WC_Payfurl_PayGLocal::class;
        $gateways[] = WC_Payfurl_Upi::class;
    }
    return $gateways;
});

add_action('plugins_loaded', function () {
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
    require_once __DIR__ . '/includes/class-wc-payment-token-payfurl.php';
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function (array $links) {
    $settings_url_params = [
        'page' => 'wc-settings',
        'tab' => 'checkout',
        'section' => 'payfurl',
    ];

    $plugin_links = [
        '<a href="' . esc_attr(admin_url(add_query_arg($settings_url_params, 'admin.php'))) . '">' . 'Settings' . '</a>',
    ];

    return array_merge($plugin_links, $links);
});
