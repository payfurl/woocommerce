<?php
/**
 * Card payments.
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cards: the browser SDK's card form (with Click to Pay) gives a single-use token, which the plugin charges with
 * the order's details. A logged-in customer can save the card (a WooCommerce payment token holding the payFURL
 * payment method ID) and pay with it later.
 */
class WC_Payfurl_Card extends WC_Payfurl {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = 'payfurl_card';

	/**
	 * Set up the gateway.
	 */
	public function __construct() {
		parent::__construct();
		$this->supports[] = 'tokenization';
	}

	/**
	 * Charges the card form's token, a card the customer saves now, or one of their saved cards.
	 *
	 * @param int $order_id The order.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order   = wc_get_order( $order_id );
		$data    = $this->get_post_data();
		$request = $this->charge_request( $order );

		// WooCommerce's key for a saved card (`new` for a new one); the Checkout block also sends `isSavedToken` and
		// `token` when the shopper picks one, but not for the card it selects at load.
		$token_id = (string) ( $data[ 'wc-' . $this->id . '-payment-token' ] ?? '' );
		if ( ( '' === $token_id || 'new' === $token_id ) && ! empty( $data['issavedtoken'] ) ) {
			$token_id = (string) ( $data['token'] ?? '' );
		}
		if ( '' !== $token_id && 'new' !== $token_id ) {
			$token = WC_Payment_Tokens::get( absint( $token_id ) );
			// Token IDs are sequential: anyone can post another customer's.
			if ( ! $token || get_current_user_id() <= 0 || $token->get_user_id() !== get_current_user_id()
				|| $token->get_gateway_id() !== $this->id ) {
				return $this->return_error(
					$order,
					__( 'That saved card can\'t be used. Please choose another payment method.', 'payfurl-extension-for-woocommerce' ),
					'saved card ' . absint( $token_id ) . ' is not the customer\'s'
				);
			}

			$request['paymentMethodId'] = $token->get_token();
			return $this->finish_payment( $order, WC_Payfurl_SDK::get_instance()->charge_by_payment_method( $request ) );
		}

		$card_token = (string) ( $data['payfurl_token'] ?? '' );
		if ( '' === $card_token ) {
			return $this->return_error( $order, __( 'Please enter your card details.', 'payfurl-extension-for-woocommerce' ), 'no card token' );
		}

		if ( ! empty( $data['wc-payfurl_card-new-payment-method'] ) && get_current_user_id() > 0 ) {
			$method = WC_Payfurl_SDK::get_instance()->create_payment_method( $card_token );
			if ( ! empty( $method['errorMessage'] ) ) {
				return $this->return_error( $order, $method['errorMessage'] );
			}
			$this->save_card( $method );

			$request['paymentMethodId'] = $method['paymentMethodId'];
			return $this->finish_payment( $order, WC_Payfurl_SDK::get_instance()->charge_by_payment_method( $request ) );
		}

		$request['token'] = $card_token;
		return $this->finish_payment( $order, WC_Payfurl_SDK::get_instance()->charge_by_token( $request ) );
	}

	/**
	 * Saves a payFURL payment method as the customer's card.
	 *
	 * @param array $method The payment method.
	 */
	private function save_card( array $method ) {
		$card   = (array) ( $method['card'] ?? array() );
		$expiry = explode( '/', (string) ( $card['expiryDate'] ?? '' ) );

		$token = new WC_Payment_Token_CC();
		$token->set_gateway_id( $this->id );
		$token->set_token( $method['paymentMethodId'] );
		$token->set_user_id( get_current_user_id() );
		$token->set_last4( substr( (string) ( $card['cardNumber'] ?? '' ), -4 ) );
		$token->set_card_type( strtolower( (string) ( $card['type'] ?? '' ) ) );
		$token->set_expiry_month( $expiry[0] ?? '' );
		$token->set_expiry_year( isset( $expiry[1] ) ? '20' . $expiry[1] : '' );
		$token->save();
	}
}
