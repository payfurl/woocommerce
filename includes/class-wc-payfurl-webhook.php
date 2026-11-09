<?php
/**
 * Transaction webhooks from payFURL.
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * POST https://<store>/?wc-api=payfurl (WC()->api_request_url( 'payfurl' )). Settles an order on hold, whose
 * charge was still pending when it was placed (PayID, PayTo). The webhook only says "look again": the status
 * comes from fetching the charge (WC_Payfurl::update_from_charge()), never from the body, and the signature only
 * keeps out requests that are not from payFURL.
 */
class WC_Payfurl_Webhook {

	/**
	 * The header payFURL's webhooks-sender signs the body in: base64(HMAC-SHA256(body, signing key)).
	 */
	public const SIGNATURE_HEADER = 'HTTP_X_PAYFURL_SIGNATURE';

	/**
	 * Handles the request and ends it (the woocommerce_api_payfurl action).
	 */
	public static function handle() {
		$body      = (string) file_get_contents( 'php://input' );
		$signature = isset( $_SERVER[ self::SIGNATURE_HEADER ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ self::SIGNATURE_HEADER ] ) ) : '';

		list( $code, $text ) = self::process( $body, $signature );
		status_header( $code );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo esc_html( $text );
		exit;
	}

	/**
	 * The response to a webhook.
	 *
	 * @param string $body      The request body.
	 * @param string $signature The X-Payfurl-Signature header.
	 * @return array{0: int, 1: string} The status code and text.
	 */
	public static function process( string $body, string $signature ): array {
		$event     = json_decode( $body, true );
		$charge_id = is_array( $event ) ? ( $event['data']['chargeId'] ?? '' ) : '';
		if ( ! is_string( $charge_id ) || '' === $charge_id ) {
			return array( 200, 'ignored: not a charge' );
		}

		$orders = wc_get_orders(
			array(
				'limit'      => 1,
				'meta_key'   => WC_Payfurl::META_CHARGE_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $charge_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		$order  = $orders[0] ?? null;
		if ( ! $order instanceof WC_Order ) {
			return array( 200, 'ignored: no order holds this charge' );
		}

		$settings = (array) get_option( 'woocommerce_payfurl_settings', array() );
		if ( ! self::is_signed_by( $body, $signature, (string) ( $settings['webhook_signing_key'] ?? '' ) ) ) {
			return array( 401, 'invalid signature' );
		}

		if ( ! $order->has_status( 'on-hold' ) ) {
			return array( 200, 'ignored: order is not on hold' );
		}

		$gateway = wc_get_payment_gateway_by_order( $order );
		if ( ! $gateway instanceof WC_Payfurl ) {
			return array( 200, 'ignored: not a payFURL order' );
		}

		$result = $gateway->update_from_charge( $order );
		return 'error' === $result ? array( 500, 'update failed' ) : array( 200, $result );
	}

	/**
	 * Whether payFURL signed the body with this key, as webhooks-sender does.
	 *
	 * @param string $body      The request body.
	 * @param string $signature The signature header.
	 * @param string $key       The account's webhook signing key.
	 * @return bool
	 */
	public static function is_signed_by( string $body, string $signature, string $key ): bool {
		return '' !== $key && hash_equals( base64_encode( hash_hmac( 'sha256', $body, $key, true ) ), $signature ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}
}
