<?php
/**
 * The plugin's test case: the payFURL API replaced by Payfurl_Fake_SDK.
 *
 * @package PayfurlExtensionForWooCommerce
 */

/**
 * Stands in for the API. Charges the plugin creates succeed for what was asked; charges the browser created are
 * whatever a test puts in $charges.
 */
class Payfurl_Fake_SDK extends WC_Payfurl_SDK {

	/** @var array[] Every call: [ method, ...arguments ]. */
	public $calls = array();

	/** @var array[] Charges by ID, for get_charge(). */
	public $charges = array();

	/** @var array|null What the next refund returns. */
	public $refund_result = null;

	public function get_charge( string $charge_id ) {
		$this->calls[] = array( 'get_charge', $charge_id );
		return $this->charges[ $charge_id ] ?? array(
			'errorMessage' => 'Invalid ChargeId',
			'errorCode'    => 400,
		);
	}

	public function charge_by_token( $data ) {
		$this->calls[] = array( 'charge_by_token', $data );
		return $this->created( $data );
	}

	public function charge_by_payment_method( $data ) {
		$this->calls[] = array( 'charge_by_payment_method', $data );
		return $this->created( $data );
	}

	public function create_payment_method( $token ) {
		$this->calls[] = array( 'create_payment_method', $token );
		return array(
			'paymentMethodId' => 'pm_saved',
			'card'            => array(
				'cardNumber' => '411111******1111',
				'type'       => 'VISA',
				'expiryDate' => '12/30',
			),
		);
	}

	public function refund( string $charge_id, float $amount, string $comment ) {
		$this->calls[] = array( 'refund', $charge_id, $amount, $comment );
		return $this->refund_result ?? array(
			'chargeId' => $charge_id,
			'status'   => 'REFUND',
		);
	}

	public function get_providers_info( $amount, $currency ) {
		return array();
	}

	/**
	 * The API's charge for a request.
	 *
	 * @param array $data The request.
	 */
	private function created( array $data ): array {
		return array(
			'chargeId'  => 'ch_created_' . count( $this->calls ),
			'status'    => 'SUCCESS',
			'amount'    => (float) $data['amount'],
			'currency'  => $data['currency'],
			'reference' => $data['reference'],
		);
	}
}

/**
 * Sets the plugin up as a store would, with the fake API.
 */
abstract class Payfurl_Test_Case extends WP_UnitTestCase {

	public const SIGNING_KEY = 'webhook-signing-key';

	/** @var Payfurl_Fake_SDK */
	protected $sdk;

	public function set_up(): void {
		parent::set_up();
		$this->sdk = new Payfurl_Fake_SDK();
		WC_Payfurl_SDK::set_instance( $this->sdk );
		update_option( 'woocommerce_currency', 'AUD' );
		update_option(
			'woocommerce_payfurl_settings',
			array(
				'enabled'             => 'yes',
				'title'               => 'Credit or debit card',
				'environment'         => 'sandbox',
				'sandbox_public_key'  => 'pk',
				'sandbox_private_key' => 'sk',
				'webhook_signing_key' => self::SIGNING_KEY,
			)
		);
		// wc_add_notice() needs a session, which the CLI has none of.
		WC()->session = new WC_Session_Handler();
		WC()->session->init();
		$_POST = array();
	}

	public function tear_down(): void {
		WC_Payfurl_SDK::set_instance( null );
		$_POST = array();
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * An unpaid order for one product, paid with a payFURL method.
	 *
	 * @param string $method The gateway ID.
	 * @param float  $price  The product's price.
	 */
	protected function order( string $method = 'payfurl_paypal', float $price = 25.0 ): WC_Order {
		$product = new WC_Product_Simple();
		$product->set_name( 'payFURL Tee' );
		$product->set_sku( 'tee-' . wp_generate_password( 6, false ) );
		$product->set_regular_price( (string) $price );
		$product->save();

		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->set_currency( 'AUD' );
		$order->set_billing_email( 'shopper@example.com' );
		$order->set_payment_method( $method );
		$order->calculate_totals();
		$order->save();
		return $order;
	}

	/**
	 * A charge for the order, as the API returns it; the browser SDK's charges have no reference.
	 *
	 * @param WC_Order $order     The order.
	 * @param array    $overrides What differs.
	 */
	protected function charge( WC_Order $order, array $overrides = array() ): array {
		$charge = array_merge(
			array(
				'chargeId'  => 'ch_' . wp_generate_password( 12, false ),
				'status'    => 'SUCCESS',
				'amount'    => (float) $order->get_total(),
				'currency'  => 'AUD',
				'reference' => null,
			),
			$overrides
		);
		$this->sdk->charges[ $charge['chargeId'] ] = $charge;
		return $charge;
	}

	/**
	 * The order as it is stored now.
	 *
	 * @param WC_Order $order The order.
	 */
	protected function reload( WC_Order $order ): WC_Order {
		return wc_get_order( $order->get_id() );
	}

	/**
	 * The API calls made, by method.
	 *
	 * @param string $method The method.
	 */
	protected function calls( string $method ): array {
		return array_values( array_filter( $this->sdk->calls, fn( $call ) => $call[0] === $method ) );
	}
}
