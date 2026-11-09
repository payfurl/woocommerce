<?php
/**
 * Payment methods whose charge the browser SDK creates.
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * PayPal, the wallets and the checkout methods (PayID, Shift, Optty, ...): the browser SDK creates the charge and
 * sends its ID with the order. The ID is only a claim: the plugin fetches the charge and checks it pays this
 * order (WC_Payfurl::charge_error()) before marking the order paid.
 */
class WC_Payfurl_Checkout extends WC_Payfurl {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = 'payfurl_checkout';

	/**
	 * Set up the gateway.
	 */
	public function __construct() {
		parent::__construct();
		$this->title = __( 'payFURL Checkout', 'payfurl-extension-for-woocommerce' );
	}

	/**
	 * Checks the charge the browser sent and pays the order with it.
	 *
	 * @param int $order_id The order.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order     = wc_get_order( $order_id );
		$data      = $this->get_post_data();
		$charge_id = sanitize_text_field( (string) ( $data['payfurl_transaction_id'] ?? '' ) );
		if ( '' === $charge_id ) {
			return $this->return_error( $order, __( 'The payment was not completed. Please try again.', 'payfurl-extension-for-woocommerce' ), 'no charge ID' );
		}

		return $this->finish_payment( $order, WC_Payfurl_SDK::get_instance()->get_charge( $charge_id ) );
	}
}
