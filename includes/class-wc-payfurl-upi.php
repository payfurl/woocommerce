<?php
/**
 * UPI payments.
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * UPI, a checkout method. The browser SDK creates the charge; WC_Payfurl_Checkout checks it.
 */
class WC_Payfurl_Upi extends WC_Payfurl_Checkout {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = 'payfurl_upi';

	/**
	 * Set up the gateway.
	 */
	public function __construct() {
		parent::__construct();
		$this->title = __( 'UPI', 'payfurl-extension-for-woocommerce' );
	}
}
