<?php
/**
 * PayGLocal payments.
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * The PayGLocal checkout method. The browser SDK creates the charge; WC_Payfurl_Checkout checks it.
 */
class WC_Payfurl_PayGLocal extends WC_Payfurl_Checkout {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = 'payfurl_payglocal';

	/**
	 * Set up the gateway.
	 */
	public function __construct() {
		parent::__construct();
		$this->title = __( 'PayGLocal', 'payfurl-extension-for-woocommerce' );
	}
}
