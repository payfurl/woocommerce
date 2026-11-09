<?php
/**
 * Google Pay payments.
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Google Pay. The browser SDK creates the charge; WC_Payfurl_Checkout checks it.
 */
class WC_Payfurl_GooglePay extends WC_Payfurl_Checkout {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = 'payfurl_googlepay';

	/**
	 * Set up the gateway.
	 */
	public function __construct() {
		parent::__construct();
		$this->title = __( 'Google Pay', 'payfurl-extension-for-woocommerce' );
	}
}
