<?php
/**
 * The calls the plugin makes to the payFURL API.
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

use payFURL\Sdk as PayFurlSDK;

/**
 * The PHP SDK, set up once per request from the gateway settings. Each call returns the API's response, or
 * `errorMessage` and `errorCode` when it failed.
 */
class WC_Payfurl_SDK {

	/**
	 * The instance.
	 *
	 * @var self|null
	 */
	protected static $instance;

	/**
	 * The instance, with the configured environment's private key.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === static::$instance ) {
			$settings = (array) get_option( 'woocommerce_payfurl_settings', array() );
			PayFurlSDK\Config::initialise( WC_Payfurl::key( $settings, 'private' ), $settings['environment'] ?? 'sandbox' );
			static::$instance = new self();
		}

		return static::$instance;
	}

	/**
	 * Replaces the instance: unit tests use it to stand in for the API.
	 *
	 * @param self|null $instance The instance, or null to set it up again from the settings.
	 */
	public static function set_instance( ?self $instance ) {
		static::$instance = $instance;
	}

	/**
	 * The payment methods the account offers for this amount and currency.
	 *
	 * @param float|string $amount   The amount.
	 * @param string       $currency The currency.
	 * @return array
	 */
	public function get_providers_info( $amount, $currency ) {
		return $this->call(
			'providers',
			fn() => ( new PayFurlSDK\Info() )->Providers(
				array(
					'amount'   => $amount,
					'currency' => $currency,
				)
			)
		);
	}

	/**
	 * Charges a single-use token from the browser SDK.
	 *
	 * @param array $data The charge request with `token`.
	 * @return array
	 */
	public function charge_by_token( $data ) {
		return $this->call( 'charge token', fn() => ( new PayFurlSDK\Charge() )->CreateWithToken( $data ) );
	}

	/**
	 * Charges a saved payment method.
	 *
	 * @param array $data The charge request with `paymentMethodId`.
	 * @return array
	 */
	public function charge_by_payment_method( $data ) {
		return $this->call( 'charge payment method', fn() => ( new PayFurlSDK\Charge() )->CreateWithPaymentMethod( $data ) );
	}

	/**
	 * Saves a single-use token as a payment method.
	 *
	 * @param string $token The token.
	 * @return array
	 */
	public function create_payment_method( $token ) {
		return $this->call( 'create payment method', fn() => ( new PayFurlSDK\PaymentMethod() )->CreateWithToken( array( 'token' => $token ) ) );
	}

	/**
	 * A charge.
	 *
	 * @param string $charge_id The charge ID.
	 * @return array
	 */
	public function get_charge( string $charge_id ) {
		return $this->call( 'get charge', fn() => ( new PayFurlSDK\Charge() )->Single( array( 'chargeId' => $charge_id ) ) );
	}

	/**
	 * Refunds part or all of a charge.
	 *
	 * @param string $charge_id The charge ID.
	 * @param float  $amount    The amount, more than 0.
	 * @param string $comment   The reason.
	 * @return array
	 */
	public function refund( string $charge_id, float $amount, string $comment ) {
		return $this->call(
			'refund',
			fn() => ( new PayFurlSDK\Charge() )->Refund(
				array(
					'chargeId' => $charge_id,
					'amount'   => $amount,
					'comment'  => $comment,
				)
			)
		);
	}

	/**
	 * Calls the API, logging the outcome (WC_Payfurl::log).
	 *
	 * @param string   $name What the call is, for the log.
	 * @param callable $call The SDK call.
	 * @return array
	 */
	protected function call( string $name, callable $call ): array {
		try {
			$result = (array) $call();
			WC_Payfurl::log( 'debug', $name . ': ' . ( $result['chargeId'] ?? '' ) . ' ' . ( $result['status'] ?? 'ok' ) );
			return $result;
		} catch ( PayFurlSDK\ResponseException $exception ) {
			WC_Payfurl::log( 'error', $name . ' failed: ' . $exception->getMessage() );
			return array(
				'errorMessage' => $exception->getMessage(),
				'errorCode'    => $exception->getCode(),
			);
		} catch ( \Exception $exception ) {
			WC_Payfurl::log( 'error', $name . ' failed: ' . $exception->getMessage() );
			return array(
				'errorMessage' => __( 'The payment service could not be reached. Please try again.', 'payfurl-extension-for-woocommerce' ),
				'errorCode'    => 0,
			);
		}
	}
}
