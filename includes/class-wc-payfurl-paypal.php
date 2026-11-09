<?php
/**
 * PayPal payments.
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * PayPal. The browser SDK creates the charge; WC_Payfurl_Checkout checks it.
 */
class WC_Payfurl_Paypal extends WC_Payfurl_Checkout {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = 'payfurl_paypal';

	/**
	 * Set up the gateway.
	 */
	public function __construct() {
		parent::__construct();
		$this->title = __( 'PayPal', 'payfurl-extension-for-woocommerce' );
	}
}
