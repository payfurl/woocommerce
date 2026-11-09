<?php
/**
 * The calls the plugin makes to the payFURL API.
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

use payFURL\Sdk\ClientOptions;
use payFURL\Sdk\Environment;
use payFURL\Sdk\Exception\PayfurlException;
use payFURL\Sdk\Model\Address;
use payFURL\Sdk\Model\NewChargePaymentMethod;
use payFURL\Sdk\Model\NewChargeToken;
use payFURL\Sdk\Model\NewPaymentMethodToken;
use payFURL\Sdk\Model\Order;
use payFURL\Sdk\Model\ProductItem;
use payFURL\Sdk\PayfurlClient;

/**
 * The PHP SDK (payfurl/phpsdk 3), with the configured environment's private key. Each call returns the API's
 * response as an array, or `errorMessage` and `errorCode` when it failed, so the gateways, the webhook and the blocks
 * integration read what they always have.
 */
class WC_Payfurl_SDK {

	/**
	 * The instance.
	 *
	 * @var self|null
	 */
	protected static $instance;

	/**
	 * How the client sends requests; null for the SDK's defaults.
	 *
	 * @var ClientOptions|null
	 */
	private $options;

	/**
	 * The client, made on the first call.
	 *
	 * @var PayfurlClient|null
	 */
	private $client;

	/**
	 * The instance for the gateway settings.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === static::$instance ) {
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
	 * The browser SDK the checkout loads, from PAYFURL_SDK_URL. /info/providers lists PayPal only for version 4 or later.
	 *
	 * @return string|null
	 */
	public static function browser_sdk_version(): ?string {
		return preg_match( '#/v(\d+\.\d+\.\d+)/#', PAYFURL_SDK_URL, $match ) ? $match[1] : null;
	}

	/**
	 * Constructor.
	 *
	 * @param ClientOptions|null $options How the client sends requests: unit tests pass the SDK's fake transport.
	 */
	public function __construct( ?ClientOptions $options = null ) {
		$this->options = $options;
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
			fn() => $this->client()->info()->providers(
				is_numeric( $amount ) ? (float) $amount : null,
				'' === (string) $currency ? null : (string) $currency,
				self::browser_sdk_version()
			)->toArray()
		);
	}

	/**
	 * Charges a single-use token from the browser SDK.
	 *
	 * @param array $data The charge request (WC_Payfurl::charge_request) with `token`.
	 * @return array
	 */
	public function charge_by_token( $data ) {
		return $this->call(
			'charge token',
			fn() => $this->client()->charges()->createWithToken(
				new NewChargeToken( ...self::charge( $data ), token: (string) $data['token'] )
			)->toArray()
		);
	}

	/**
	 * Charges a saved payment method.
	 *
	 * @param array $data The charge request (WC_Payfurl::charge_request) with `paymentMethodId`.
	 * @return array
	 */
	public function charge_by_payment_method( $data ) {
		return $this->call(
			'charge payment method',
			fn() => $this->client()->charges()->createWithPaymentMethod(
				new NewChargePaymentMethod( ...self::charge( $data ), paymentMethodId: (string) $data['paymentMethodId'] )
			)->toArray()
		);
	}

	/**
	 * Saves a single-use token as a payment method.
	 *
	 * @param string $token The token.
	 * @return array
	 */
	public function create_payment_method( $token ) {
		return $this->call(
			'create payment method',
			fn() => $this->client()->paymentMethods()->createWithToken( new NewPaymentMethodToken( token: (string) $token ) )->toArray()
		);
	}

	/**
	 * A charge.
	 *
	 * @param string $charge_id The charge ID.
	 * @return array
	 */
	public function get_charge( string $charge_id ) {
		return $this->call( 'get charge', fn() => $this->client()->charges()->get( $charge_id )->toArray() );
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
			fn() => $this->client()->charges()->refund( $charge_id, $amount > 0 ? $amount : null, '' === $comment ? null : $comment )->toArray()
		);
	}

	/**
	 * The client for the configured environment's private key.
	 *
	 * @return PayfurlClient
	 * @throws \InvalidArgumentException When the private key is not set.
	 */
	protected function client(): PayfurlClient {
		if ( null === $this->client ) {
			$settings     = (array) get_option( 'woocommerce_payfurl_settings', array() );
			$this->client = new PayfurlClient(
				WC_Payfurl::key( $settings, 'private' ),
				Environment::tryFrom( (string) ( $settings['environment'] ?? '' ) ) ?? Environment::Sandbox,
				$this->options ?? new ClientOptions()
			);
		}

		return $this->client;
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
		} catch ( PayfurlException $exception ) {
			WC_Payfurl::log( 'error', $name . ' failed: ' . $exception->getMessage() );
			return array(
				'errorMessage' => $exception->getMessage(),
				'errorCode'    => $exception->getCode(),
			);
		} catch ( \Exception $exception ) {
			// No private key, or a request the SDK refused to build.
			WC_Payfurl::log( 'error', $name . ' failed: ' . $exception->getMessage() );
			return array(
				'errorMessage' => __( 'The payment service could not be reached. Please try again.', 'payfurl-extension-for-woocommerce' ),
				'errorCode'    => 0,
			);
		}
	}

	/**
	 * The named arguments of a charge request, from WC_Payfurl::charge_request.
	 *
	 * @param array $data The charge request.
	 * @return array<string, mixed>
	 */
	private static function charge( array $data ): array {
		$order = is_array( $data['order'] ?? null ) ? $data['order'] : null;
		$items = array();
		foreach ( (array) ( $order['items'] ?? array() ) as $item ) {
			$items[] = new ProductItem(
				productCode: self::string( $item, 'productCode' ),
				description: self::string( $item, 'description' ),
				quantity: isset( $item['quantity'] ) ? (int) $item['quantity'] : null,
				amount: isset( $item['amount'] ) ? (float) $item['amount'] : null,
				taxAmount: isset( $item['taxAmount'] ) ? (float) $item['taxAmount'] : null
			);
		}
		$address = is_array( $data['address'] ?? null ) ? $data['address'] : null;

		return array(
			'amount'    => (float) $data['amount'],
			'currency'  => self::string( $data, 'currency' ),
			'reference' => self::string( $data, 'reference' ),
			'email'     => self::string( $data, 'email' ),
			'phone'     => self::string( $data, 'phone' ),
			'firstName' => self::string( $data, 'firstName' ),
			'lastName'  => self::string( $data, 'lastName' ),
			'order'     => null === $order ? null : new Order(
				orderNumber: self::string( $order, 'orderNumber' ),
				freightAmount: isset( $order['freightAmount'] ) ? (float) $order['freightAmount'] : null,
				items: $items
			),
			'address'   => null === $address ? null : new Address(
				line1: self::string( $address, 'line1' ),
				line2: self::string( $address, 'line2' ),
				city: self::string( $address, 'city' ),
				country: self::string( $address, 'country' ),
				postalCode: self::string( $address, 'postalCode' ),
				state: self::string( $address, 'state' )
			),
		);
	}

	/**
	 * A value as a string, or null when it is missing or empty.
	 *
	 * @param array  $data The array.
	 * @param string $key  The key.
	 * @return string|null
	 */
	private static function string( array $data, string $key ): ?string {
		$value = $data[ $key ] ?? null;
		return is_scalar( $value ) && '' !== (string) $value ? (string) $value : null;
	}
}
