<?php
/**
 * Loads WordPress's test suite with WooCommerce and this plugin, in wp-env's tests-cli container.
 *
 * @package PayfurlExtensionForWooCommerce
 */

$root = dirname( __DIR__, 2 );
require_once $root . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );

$tests_dir = $root . '/vendor/wp-phpunit/wp-phpunit';
require_once $tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	function () use ( $root ) {
		require_once WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
		require_once $root . '/woocommerce-payfurl.php';
	}
);

tests_add_filter(
	'setup_theme',
	function () {
		// WooCommerce's tables and roles, as its own test suite installs them.
		WC_Install::install();
		$GLOBALS['wp_roles'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		wp_roles();
	}
);

require_once $tests_dir . '/includes/bootstrap.php';
require_once __DIR__ . '/class-payfurl-test-case.php';
