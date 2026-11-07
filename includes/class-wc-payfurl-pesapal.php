<?php
/**
 * Pesapal payments.
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Pesapal checkout method. The browser SDK creates the charge; WC_Payfurl_Checkout checks it.
 */
class WC_Payfurl_Pesapal extends WC_Payfurl_Checkout {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = 'payfurl_pesapal';

	/**
	 * Set up the gateway.
	 */
	public function __construct() {
		parent::__construct();
		$this->title = __( 'Pesapal', 'payfurl-extension-for-woocommerce' );
	}
}
