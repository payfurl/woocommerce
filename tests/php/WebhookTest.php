<?php
/**
 * PAY-5636: a signed webhook settles an order on hold from the charge's status, which it fetches.
 *
 * @package PayfurlExtensionForWooCommerce
 */

class WebhookTest extends Payfurl_Test_Case {

	private function body( string $charge_id ): string {
		return wp_json_encode(
			array(
				'meta' => array( 'eventType' => 'transaction.status.changed' ),
				'data' => array( 'chargeId' => $charge_id ),
			)
		);
	}

	private function sign( string $body, string $key = self::SIGNING_KEY ): string {
		return base64_encode( hash_hmac( 'sha256', $body, $key, true ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	private function order_on_hold(): array {
		$order  = $this->order( 'payfurl_azupay' );
		$charge = $this->charge( $order, array( 'status' => 'PENDING' ) );
		$_POST  = array( 'payfurl_transaction_id' => $charge['chargeId'] );
		( new WC_Payfurl_PayId() )->process_payment( $order->get_id() );
		$this->assertSame( 'on-hold', $this->reload( $order )->get_status() );
		return array( $order, $charge['chargeId'] );
	}

	public static function settled(): array {
		return array(
			'succeeded' => array( 'SUCCESS', 200, 'completed', 'processing' ),
			'failed'    => array( 'FAILED', 200, 'cancelled', 'cancelled' ),
			'pending'   => array( 'PENDING', 200, 'pending', 'on-hold' ),
		);
	}

	/**
	 * @dataProvider settled
	 */
	public function test_a_signed_webhook_settles_the_order_from_the_charge( string $status, int $code, string $text, string $order_status ) {
		list( $order, $charge_id ) = $this->order_on_hold();
		$this->sdk->charges[ $charge_id ]['status'] = $status;
		$body                                       = $this->body( $charge_id );

		$this->assertSame( array( $code, $text ), WC_Payfurl_Webhook::process( $body, $this->sign( $body ) ) );
		$this->assertSame( $order_status, $this->reload( $order )->get_status() );
	}

	public function test_the_status_comes_from_payfurl_not_the_body() {
		list( $order, $charge_id ) = $this->order_on_hold();
		$body                      = wp_json_encode(
			array(
				'data' => array(
					'chargeId' => $charge_id,
					'status'   => 'SUCCESS',
				),
			)
		);

		$this->assertSame( array( 200, 'pending' ), WC_Payfurl_Webhook::process( $body, $this->sign( $body ) ) );
		$this->assertSame( 'on-hold', $this->reload( $order )->get_status() );
	}

	public function test_a_wrong_signature_changes_nothing() {
		list( $order, $charge_id ) = $this->order_on_hold();
		$this->sdk->charges[ $charge_id ]['status'] = 'SUCCESS';
		$body                                       = $this->body( $charge_id );

		$this->assertSame( array( 401, 'invalid signature' ), WC_Payfurl_Webhook::process( $body, $this->sign( $body, 'not the key' ) ) );
		$this->assertSame( array( 401, 'invalid signature' ), WC_Payfurl_Webhook::process( $body, '' ) );
		$this->assertSame( 'on-hold', $this->reload( $order )->get_status() );
	}

	public function test_no_signing_key_accepts_nothing() {
		list( , $charge_id ) = $this->order_on_hold();
		update_option( 'woocommerce_payfurl_settings', array_merge( get_option( 'woocommerce_payfurl_settings' ), array( 'webhook_signing_key' => '' ) ) );
		$body = $this->body( $charge_id );

		$this->assertSame( array( 401, 'invalid signature' ), WC_Payfurl_Webhook::process( $body, $this->sign( $body, '' ) ) );
	}

	public function test_requests_about_nothing_the_store_holds_are_ignored() {
		$this->assertSame( array( 200, 'ignored: not a charge' ), WC_Payfurl_Webhook::process( '{"data":{}}', '' ) );
		$this->assertSame( array( 200, 'ignored: not a charge' ), WC_Payfurl_Webhook::process( 'not json', '' ) );
		$this->assertSame( array( 200, 'ignored: no order holds this charge' ), WC_Payfurl_Webhook::process( $this->body( 'ch_unknown' ), '' ) );
	}

	public function test_an_order_not_on_hold_is_left_alone() {
		list( $order, $charge_id ) = $this->order_on_hold();
		$order->update_status( 'processing' );
		$body = $this->body( $charge_id );

		$this->assertSame( array( 200, 'ignored: order is not on hold' ), WC_Payfurl_Webhook::process( $body, $this->sign( $body ) ) );
		// Only the checkout fetched the charge.
		$this->assertCount( 1, $this->calls( 'get_charge' ) );
	}

	public function test_the_signing_key_saved_in_the_settings_checks_the_signature() {
		// The settings round trip: saved through the admin form, read by the webhook (Magento's was decrypted twice).
		$_POST = array(
			'woocommerce_payfurl_enabled'             => '1',
			'woocommerce_payfurl_environment'         => 'sandbox',
			'woocommerce_payfurl_webhook_signing_key' => ' saved-in-the-admin ',
		);
		( new WC_Payfurl() )->process_admin_options();
		list( $order, $charge_id ) = $this->order_on_hold();
		$this->sdk->charges[ $charge_id ]['status'] = 'SUCCESS';
		$body                                       = $this->body( $charge_id );

		$this->assertSame( array( 200, 'completed' ), WC_Payfurl_Webhook::process( $body, $this->sign( $body, 'saved-in-the-admin' ) ) );
		$this->assertTrue( $this->reload( $order )->is_paid() );
	}
}
