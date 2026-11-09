<?php
/**
 * PAY-5636: refunds from the admin go through payFURL (1.x returned false).
 *
 * @package PayfurlExtensionForWooCommerce
 */

class RefundTest extends Payfurl_Test_Case {

	private function paid_order( string $method = 'payfurl_card' ): WC_Order {
		$order = $this->order( $method );
		$order->update_meta_data( WC_Payfurl::META_CHARGE_ID, 'ch_paid' );
		$order->payment_complete( 'ch_paid' );
		return $this->reload( $order );
	}

	public function test_a_partial_refund_refunds_that_amount_of_the_charge() {
		$order = $this->paid_order();

		$refund = wc_create_refund(
			array(
				'order_id'       => $order->get_id(),
				'amount'         => 10,
				'reason'         => 'Damaged',
				'refund_payment' => true,
			)
		);

		$this->assertInstanceOf( WC_Order_Refund::class, $refund );
		$this->assertSame( array( array( 'refund', 'ch_paid', 10.0, 'Damaged' ) ), $this->sdk->calls );
	}

	public function test_a_1x_order_is_refunded_from_its_meta() {
		$order = $this->order( 'payfurl_paypal' );
		// 1.x saved the charge ID in the meta only, and completed the order without a transaction ID.
		$order->update_meta_data( WC_Payfurl::META_CHARGE_ID, 'ch_1x' );
		$order->payment_complete();

		$this->assertTrue( ( new WC_Payfurl_Paypal() )->process_refund( $order->get_id(), 5, '' ) );
		$this->assertSame( 'ch_1x', $this->calls( 'refund' )[0][1] );
	}

	public function test_payfurls_refusal_reaches_the_admin() {
		$order                    = $this->paid_order();
		$this->sdk->refund_result = array(
			'errorMessage' => 'Refund amount exceeds the charge',
			'errorCode'    => 400,
		);

		$result = ( new WC_Payfurl_Card() )->process_refund( $order->get_id(), 500, '' );

		$this->assertWPError( $result );
		$this->assertSame( 'Refund amount exceeds the charge', $result->get_error_message() );
	}

	public function test_no_amount_refunds_nothing() {
		$order = $this->paid_order();

		$this->assertWPError( ( new WC_Payfurl_Card() )->process_refund( $order->get_id(), 0, '' ) );
		$this->assertWPError( ( new WC_Payfurl_Card() )->process_refund( $order->get_id(), null, '' ) );
		$this->assertSame( array(), $this->sdk->calls );
	}

	public function test_the_admin_finds_every_payment_methods_gateway() {
		set_current_screen( 'edit-shop_order' );
		WC()->payment_gateways()->init();

		foreach ( array( 'payfurl_card', 'payfurl_paypal', 'payfurl_azupay', 'payfurl_payto' ) as $method ) {
			$gateway = wc_get_payment_gateway_by_order( $this->order( $method ) );
			$this->assertInstanceOf( WC_Payfurl::class, $gateway, $method );
			$this->assertTrue( $gateway->supports( 'refunds' ), $method );
		}
		set_current_screen( 'front' );
	}
}
