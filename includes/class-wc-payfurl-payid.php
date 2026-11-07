<?php
/**
 * PayID payments.
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * PayID, the Azupay checkout method (gateway ID payfurl_azupay, its provider type). The browser SDK creates the charge; WC_Payfurl_Checkout checks it.
 */
class WC_Payfurl_PayId extends WC_Payfurl_Checkout {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = 'payfurl_azupay';

	/**
	 * Set up the gateway.
	 */
	public function __construct() {
		parent::__construct();
		$this->title = __( 'PayID', 'payfurl-extension-for-woocommerce' );
	}
}
