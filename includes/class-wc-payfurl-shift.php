<?php
/**
 * Shift payments.
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Shift checkout method. The browser SDK creates the charge; WC_Payfurl_Checkout checks it.
 */
class WC_Payfurl_Shift extends WC_Payfurl_Checkout {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = 'payfurl_shift';

	/**
	 * Set up the gateway.
	 */
	public function __construct() {
		parent::__construct();
		$this->title = __( 'Shift', 'payfurl-extension-for-woocommerce' );
	}
}
