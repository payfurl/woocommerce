<?php
/**
 * The payFURL gateway: settings, charge verification, refunds and pending payments.
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * The settings gateway (`payfurl`, listed in the admin only) and the base of every payFURL payment method.
 *
 * All payment methods share this gateway's settings (`woocommerce_payfurl_settings`). Each order is paid with a
 * payFURL charge, which is checked with payFURL before the order is marked paid: see finish_payment().
 */
class WC_Payfurl extends WC_Payment_Gateway {

	/**
	 * The order meta that holds the payFURL charge ID (also the order's transaction ID since 2.0.0).
	 */
	public const META_CHARGE_ID = 'PayfurlTransactionId';

	public const STATUS_SUCCESS = 'SUCCESS';

	/**
	 * Charge statuses that may still succeed: the order is put on hold until a webhook or the admin's
	 * "Check the payFURL payment" order action settles it.
	 */
	public const PENDING_STATUSES = array( 'PENDING', 'PENDING_CHECKOUT' );

	/**
	 * The payment methods shown at checkout, one gateway each.
	 */
	public const PAYMENT_METHODS = array(
		'WC_Payfurl_Card',
		'WC_Payfurl_Paypal',
		'WC_Payfurl_Checkout',
		'WC_Payfurl_PayTo',
		'WC_Payfurl_GooglePay',
		'WC_Payfurl_ApplePay',
		'WC_Payfurl_Shift',
		'WC_Payfurl_Optty',
		'WC_Payfurl_Pesapal',
		'WC_Payfurl_PayByAccount',
		'WC_Payfurl_PayId',
		'WC_Payfurl_PayGLocal',
		'WC_Payfurl_Upi',
	);

	/**
	 * Gateway ID. Payment methods override it; their settings stay this gateway's (get_option_key()).
	 *
	 * @var string
	 */
	public $id = 'payfurl';

	/**
	 * Set up the gateway from the shared settings.
	 */
	public function __construct() {
		// WooCommerce's Payments settings list only this gateway: the payment methods have no admin title or
		// description, which makes them "shell" gateways that the list hides behind their extension's main one.
		$is_settings_gateway      = self::class === static::class;
		$this->has_fields         = true;
		$this->method_title       = $is_settings_gateway ? 'payFURL' : '';
		$this->method_description = $is_settings_gateway ? __( 'payFURL payment orchestration: cards, PayPal, Google Pay, Apple Pay, PayTo, PayID and more. These settings are shared by every payFURL payment method.', 'payfurl-extension-for-woocommerce' ) : '';
		$this->supports           = array( 'products', 'refunds' );

		$this->init_form_fields();
		$this->init_settings();
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->get_option( 'enabled' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Every payment method reads and saves the `payfurl` gateway's settings, as 1.x did.
	 *
	 * @return string
	 */
	public function get_option_key() {
		return $this->plugin_id . 'payfurl_settings';
	}

	/**
	 * The settings form.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'                => array(
				'title'   => __( 'Enable/Disable', 'payfurl-extension-for-woocommerce' ),
				'label'   => __( 'Enable payFURL', 'payfurl-extension-for-woocommerce' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),
			'enable_applepay'        => array(
				'title'   => __( 'Apple Pay', 'payfurl-extension-for-woocommerce' ),
				'label'   => __( 'Enable Apple Pay', 'payfurl-extension-for-woocommerce' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),
			'enable_googlepay'       => array(
				'title'   => __( 'Google Pay', 'payfurl-extension-for-woocommerce' ),
				'label'   => __( 'Enable Google Pay', 'payfurl-extension-for-woocommerce' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),
			'title'                  => array(
				'title'       => __( 'Title', 'payfurl-extension-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'The card payment method\'s name at checkout.', 'payfurl-extension-for-woocommerce' ),
				'default'     => __( 'Debit / Credit Card', 'payfurl-extension-for-woocommerce' ),
			),
			'environment'            => array(
				'title'   => __( 'Environment', 'payfurl-extension-for-woocommerce' ),
				'type'    => 'select',
				'default' => 'sandbox',
				'options' => array(
					'sandbox'    => __( 'Sandbox', 'payfurl-extension-for-woocommerce' ),
					'production' => __( 'Production', 'payfurl-extension-for-woocommerce' ),
				),
			),
			'sandbox_public_key'     => array(
				'title' => __( 'Sandbox Public Key', 'payfurl-extension-for-woocommerce' ),
				'type'  => 'password',
			),
			'sandbox_private_key'    => array(
				'title' => __( 'Sandbox Private Key', 'payfurl-extension-for-woocommerce' ),
				'type'  => 'password',
			),
			'production_public_key'  => array(
				'title' => __( 'Production Public Key', 'payfurl-extension-for-woocommerce' ),
				'type'  => 'password',
			),
			'production_private_key' => array(
				'title' => __( 'Production Private Key', 'payfurl-extension-for-woocommerce' ),
				'type'  => 'password',
			),
			'webhook_signing_key'    => array(
				'title'       => __( 'Webhook signing key', 'payfurl-extension-for-woocommerce' ),
				'type'        => 'password',
				'description' => sprintf(
					/* translators: %s: the store's webhook URL */
					__( 'The Webhook Secret Key from the Account page of the payFURL portal. Add a transaction webhook for %s on its Webhooks page: it completes or cancels orders whose payment was still pending (PayID, PayTo).', 'payfurl-extension-for-woocommerce' ),
					'<code>' . esc_html( WC()->api_request_url( 'payfurl' ) ) . '</code>'
				),
			),
			'debug'                  => array(
				'title'       => __( 'Debug', 'payfurl-extension-for-woocommerce' ),
				'label'       => __( 'Enable debug mode', 'payfurl-extension-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Logs each payFURL call (WooCommerce > Status > Logs, source payfurl) and turns on the browser SDK\'s console log.', 'payfurl-extension-for-woocommerce' ),
				'default'     => 'no',
			),
		);
	}

	/**
	 * The public or private key of the configured environment. 1.x's `local_*` and `development_*` keys have no
	 * field in the form: they are for payFURL's own test stores.
	 *
	 * @param array  $settings The gateway settings.
	 * @param string $kind     `public` or `private`.
	 * @return string
	 */
	public static function key( array $settings, string $kind ): string {
		$environment = $settings['environment'] ?? 'sandbox';
		return (string) ( $settings[ $environment . '_' . $kind . '_key' ] ?? '' );
	}

	/**
	 * The browser SDK's public key.
	 *
	 * @return string
	 */
	public function get_public_key() {
		return self::key( $this->settings, 'public' );
	}

	/**
	 * The payment forms are checkout block components: the classic checkout, the pay-for-order page and My
	 * Account's "Add payment method" would show them empty, so the methods are not offered there. The settings
	 * gateway is never offered: it has no payment form.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( self::class === static::class || ! parent::is_available() ) {
			return false;
		}
		if ( is_checkout_pay_page() || is_add_payment_method_page() ) {
			return false;
		}
		if ( is_checkout() && class_exists( '\Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' )
			&& ! \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::is_checkout_block_default() ) {
			return false;
		}
		return true;
	}

	/**
	 * The settings gateway takes no payments: only its payment methods do.
	 *
	 * @param int $order_id The order.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		return $this->return_error( wc_get_order( $order_id ), __( 'Choose a payment method.', 'payfurl-extension-for-woocommerce' ), 'no payment method' );
	}

	/**
	 * Refunds through payFURL. 1.x orders have the charge ID in the order meta only.
	 *
	 * @param int        $order_id The order.
	 * @param float|null $amount   The amount to refund.
	 * @param string     $reason   The admin's reason.
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order     = wc_get_order( $order_id );
		$charge_id = $order ? (string) $order->get_meta( self::META_CHARGE_ID ) : '';
		if ( '' === $charge_id && $order ) {
			$charge_id = (string) $order->get_transaction_id();
		}
		if ( '' === $charge_id ) {
			return new WP_Error( 'payfurl_refund', __( 'This order has no payFURL charge to refund.', 'payfurl-extension-for-woocommerce' ) );
		}
		if ( null === $amount || (float) $amount <= 0 ) {
			// The API refunds the whole charge when no amount is given: never by accident.
			return new WP_Error( 'payfurl_refund', __( 'Enter an amount to refund.', 'payfurl-extension-for-woocommerce' ) );
		}

		$result = WC_Payfurl_SDK::get_instance()->refund( $charge_id, (float) $amount, (string) $reason );
		if ( ! empty( $result['errorMessage'] ) ) {
			return new WP_Error( 'payfurl_refund', $result['errorMessage'] );
		}

		$order->add_order_note(
			sprintf(
				/* translators: 1: refunded amount, 2: payFURL charge ID */
				__( 'Refunded %1$s through payFURL (charge %2$s).', 'payfurl-extension-for-woocommerce' ),
				wp_strip_all_tags( wc_price( (float) $amount, array( 'currency' => $order->get_currency() ) ) ),
				$charge_id
			)
		);
		return true;
	}

	/**
	 * The charge request for a charge the plugin creates (card, saved card, PayTo).
	 *
	 * @param WC_Order $order The order.
	 * @return array
	 */
	public function charge_request( WC_Order $order ): array {
		$decimals = wc_get_price_decimals();
		$items    = array();
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}
			$product  = $item->get_product();
			$quantity = max( 1, (int) $item->get_quantity() );
			$items[]  = array(
				// A deleted product leaves its line on the order.
				'productCode' => $product ? ( $product->get_sku() ? $product->get_sku() : (string) $product->get_id() ) : '',
				'description' => $item->get_name(),
				'quantity'    => $quantity,
				// What the shopper paid for one, after discounts and before tax, and its tax.
				'amount'      => round( (float) $item->get_total() / $quantity, $decimals ),
				'taxAmount'   => round( (float) $item->get_total_tax() / $quantity, $decimals ),
			);
		}

		return array(
			'amount'    => $order->get_total(),
			'currency'  => $order->get_currency(),
			'reference' => self::reference( $order ),
			'email'     => $order->get_billing_email(),
			'phone'     => $order->get_billing_phone(),
			'firstName' => $order->get_billing_first_name(),
			'lastName'  => $order->get_billing_last_name(),
			'order'     => array(
				'orderNumber'   => (string) $order->get_order_number(),
				'freightAmount' => $order->get_shipping_total(),
				'items'         => $items,
			),
			'address'   => array(
				'line1'      => $order->get_billing_address_1(),
				'line2'      => $order->get_billing_address_2(),
				'city'       => $order->get_billing_city(),
				'state'      => $order->get_billing_state(),
				'postalCode' => $order->get_billing_postcode(),
				'country'    => $order->get_billing_country(),
			),
		);
	}

	/**
	 * The charge reference of an order: `Order #<order number>`.
	 *
	 * @param WC_Order $order The order.
	 * @return string
	 */
	public static function reference( WC_Order $order ): string {
		return 'Order #' . $order->get_order_number();
	}

	/**
	 * Marks the order paid with a payFURL charge, once the charge is checked: see charge_error(). A charge that
	 * is still pending puts the order on hold.
	 *
	 * @param WC_Order $order  The order.
	 * @param array    $charge The charge, as the API returns it, or the SDK wrapper's error.
	 * @return array The process_payment() result.
	 */
	protected function finish_payment( WC_Order $order, array $charge ): array {
		if ( ! empty( $charge['errorMessage'] ) ) {
			return $this->return_error( $order, $charge['errorMessage'] );
		}

		$error = $this->charge_error( $order, $charge );
		if ( null !== $error ) {
			return $this->return_error(
				$order,
				__( 'Your payment could not be confirmed. Please try again or use another payment method.', 'payfurl-extension-for-woocommerce' ),
				$error . ' (' . ( $charge['chargeId'] ?? '' ) . ')'
			);
		}

		$charge_id = (string) $charge['chargeId'];
		$order->update_meta_data( self::META_CHARGE_ID, $charge_id );
		if ( self::STATUS_SUCCESS === $charge['status'] ) {
			// Saves the order, sets the transaction ID and reduces stock.
			$order->payment_complete( $charge_id );
		} else {
			$order->set_transaction_id( $charge_id );
			$order->update_status(
				'on-hold',
				sprintf(
					/* translators: %s: payFURL charge ID */
					__( 'payFURL charge %s is pending. The order is completed or cancelled when it settles.', 'payfurl-extension-for-woocommerce' ),
					$charge_id
				)
			);
		}

		if ( WC()->cart instanceof WC_Cart ) {
			WC()->cart->empty_cart();
		}
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Why the charge can't pay this order, or null when it can. Nothing the browser sends is trusted: the charge
	 * must exist, have succeeded (or still be pending), be for the order's total and currency, carry the order's
	 * reference when it has one (charges the browser SDK creates have none), and not pay another order already.
	 *
	 * @param WC_Order $order  The order.
	 * @param array    $charge The charge.
	 * @return string|null
	 */
	public function charge_error( WC_Order $order, array $charge ): ?string {
		$charge_id = (string) ( $charge['chargeId'] ?? '' );
		if ( '' === $charge_id ) {
			return 'charge_missing';
		}

		$status = (string) ( $charge['status'] ?? '' );
		if ( self::STATUS_SUCCESS !== $status && ! in_array( $status, self::PENDING_STATUSES, true ) ) {
			return 'charge_status_' . strtolower( '' !== $status ? $status : 'unknown' );
		}

		if ( strtoupper( (string) ( $charge['currency'] ?? '' ) ) !== strtoupper( $order->get_currency() ) ) {
			return 'charge_currency_mismatch';
		}

		$decimals = wc_get_price_decimals();
		if ( round( (float) ( $charge['amount'] ?? 0 ), $decimals ) !== round( (float) $order->get_total(), $decimals ) ) {
			return 'charge_amount_mismatch';
		}

		$reference = (string) ( $charge['reference'] ?? '' );
		if ( '' !== $reference && self::reference( $order ) !== $reference ) {
			return 'charge_reference_mismatch';
		}

		// ponytail: two checkouts racing with one charge ID can both pass this; closing it needs a lock per charge ID.
		$holders = wc_get_orders(
			array(
				'limit'      => 2,
				'return'     => 'ids',
				'meta_key'   => self::META_CHARGE_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $charge_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		if ( array_diff( $holders, array( $order->get_id() ) ) ) {
			return 'charge_already_used';
		}

		return null;
	}

	/**
	 * Settles an order on hold from its charge's current status: completes it on SUCCESS, cancels it when the
	 * charge failed, and leaves it on hold while the charge is pending. The webhook and the admin's order action
	 * call it; the status always comes from payFURL.
	 *
	 * @param WC_Order $order The order.
	 * @return string `completed`, `cancelled`, `pending`, `ignored` or `error`.
	 */
	public function update_from_charge( WC_Order $order ): string {
		$charge_id = (string) $order->get_meta( self::META_CHARGE_ID );
		if ( '' === $charge_id || ! $order->has_status( 'on-hold' ) ) {
			return 'ignored';
		}

		$charge = WC_Payfurl_SDK::get_instance()->get_charge( $charge_id );
		if ( ! empty( $charge['errorMessage'] ) ) {
			return 'error';
		}

		$status = (string) ( $charge['status'] ?? '' );
		if ( self::STATUS_SUCCESS === $status ) {
			$order->add_order_note( __( 'The payFURL charge succeeded.', 'payfurl-extension-for-woocommerce' ) );
			$order->payment_complete( $charge_id );
			return 'completed';
		}
		if ( in_array( $status, self::PENDING_STATUSES, true ) ) {
			$order->add_order_note( __( 'The payFURL charge is still pending.', 'payfurl-extension-for-woocommerce' ) );
			return 'pending';
		}

		$order->update_status(
			'cancelled',
			sprintf(
				/* translators: %s: payFURL charge status, such as FAILED */
				__( 'The payFURL charge did not succeed (%s).', 'payfurl-extension-for-woocommerce' ),
				$status
			)
		);
		return 'cancelled';
	}

	/**
	 * Fails the payment: the shopper sees the message, the order is marked failed and the reason logged.
	 *
	 * @param WC_Order|false $order   The order.
	 * @param string         $message What the shopper sees.
	 * @param string         $reason  What the log says, when it differs.
	 * @return array
	 */
	protected function return_error( $order, string $message, string $reason = '' ): array {
		self::log( 'error', ( $order ? 'Order ' . $order->get_order_number() . ': ' : '' ) . ( '' !== $reason ? $reason : $message ) );
		wc_add_notice( $message, 'error' );
		if ( $order ) {
			$order->update_status( 'failed', $message );
		}
		return array(
			'result'  => 'failure',
			'message' => $message,
		);
	}

	/**
	 * Logs to WooCommerce's log (source `payfurl`): errors always, the rest in debug mode.
	 *
	 * @param string $level   A PSR-3 level.
	 * @param string $message The message.
	 */
	public static function log( string $level, string $message ) {
		$settings = (array) get_option( 'woocommerce_payfurl_settings', array() );
		if ( 'error' === $level || 'yes' === ( $settings['debug'] ?? 'no' ) ) {
			wc_get_logger()->log( $level, $message, array( 'source' => 'payfurl' ) );
		}
	}
}
