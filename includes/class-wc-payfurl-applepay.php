<?php
/**
 * Apple Pay payments.
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Apple Pay. The browser SDK creates the charge; WC_Payfurl_Checkout checks it.
 */
class WC_Payfurl_ApplePay extends WC_Payfurl_Checkout {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = 'payfurl_applepay';

	/**
	 * Set up the gateway.
	 */
	public function __construct() {
		parent::__construct();
		$this->title = __( 'Apple Pay', 'payfurl-extension-for-woocommerce' );
	}
}
