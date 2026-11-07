<?php
/**
 * PayTo payments.
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * PayTo: the browser SDK's PayTo form gives a token, which the plugin charges. The charge is usually pending
 * until the payer approves the agreement, so the order waits on hold (WC_Payfurl::finish_payment()).
 */
class WC_Payfurl_PayTo extends WC_Payfurl {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = 'payfurl_payto';

	/**
	 * Set up the gateway.
	 */
	public function __construct() {
		parent::__construct();
		$this->title = __( 'PayTo', 'payfurl-extension-for-woocommerce' );
	}

	/**
	 * Charges the PayTo form's token.
	 *
	 * @param int $order_id The order.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		$data  = $this->get_post_data();
		$token = (string) ( $data['payfurl_token'] ?? '' );
		if ( '' === $token ) {
			return $this->return_error( $order, __( 'Please complete the PayTo details.', 'payfurl-extension-for-woocommerce' ), 'no PayTo token' );
		}

		$request          = $this->charge_request( $order );
		$request['token'] = $token;
		return $this->finish_payment( $order, WC_Payfurl_SDK::get_instance()->charge_by_token( $request ) );
	}
}
