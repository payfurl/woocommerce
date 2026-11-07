<?php
/**
 * Optty payments.
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Optty checkout method. The browser SDK creates the charge; WC_Payfurl_Checkout checks it.
 */
class WC_Payfurl_Optty extends WC_Payfurl_Checkout {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = 'payfurl_optty';

	/**
	 * Set up the gateway.
	 */
	public function __construct() {
		parent::__construct();
		$this->title = __( 'Optty', 'payfurl-extension-for-woocommerce' );
	}
}
