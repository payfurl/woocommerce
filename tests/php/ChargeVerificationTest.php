<?php
/**
 * PAY-5636: an order is paid only by a charge payFURL confirms is for it. 1.x completed an order with whatever
 * charge ID the browser sent.
 *
 * @package PayfurlExtensionForWooCommerce
 */

class ChargeVerificationTest extends Payfurl_Test_Case {

	private function pay_with( WC_Order $order, string $charge_id, string $gateway = WC_Payfurl_Paypal::class ): array {
		$_POST = array( 'payfurl_transaction_id' => $charge_id );
		return ( new $gateway() )->process_payment( $order->get_id() );
	}

	public function test_a_charge_that_succeeded_for_the_order_pays_it() {
		$order  = $this->order();
		$charge = $this->charge( $order );

		$result = $this->pay_with( $order, $charge['chargeId'] );

		$this->assertSame( 'success', $result['result'] );
		$paid = $this->reload( $order );
		$this->assertSame( 'processing', $paid->get_status() );
		$this->assertTrue( $paid->is_paid() );
		$this->assertSame( $charge['chargeId'], $paid->get_transaction_id() );
		$this->assertSame( $charge['chargeId'], $paid->get_meta( WC_Payfurl::META_CHARGE_ID ) );
	}

	public function test_a_checkout_method_is_checked_the_same_way() {
		$order  = $this->order( 'payfurl_azupay' );
		$charge = $this->charge( $order, array( 'status' => 'FAILED' ) );

		$this->assertSame( 'failure', $this->pay_with( $order, $charge['chargeId'], WC_Payfurl_PayId::class )['result'] );
		$this->assertFalse( $this->reload( $order )->is_paid() );
	}

	public static function charges_that_do_not_pay_the_order(): array {
		return array(
			'failed'               => array( array( 'status' => 'FAILED' ) ),
			'cancelled'            => array( array( 'status' => 'AUTHORISE_CANCELLED' ) ),
			'another currency'     => array( array( 'currency' => 'USD' ) ),
			'less than the total'  => array( array( 'amount' => 24.99 ) ),
			'another reference'    => array( array( 'reference' => 'Order #999999' ) ),
			'no charge ID at all'  => array( array( 'chargeId' => '' ) ),
		);
	}

	/**
	 * @dataProvider charges_that_do_not_pay_the_order
	 */
	public function test_a_charge_that_does_not_pay_the_order_fails_it( array $overrides ) {
		$order  = $this->order();
		$charge = $this->charge( $order, $overrides );

		if ( '' === $charge['chargeId'] ) {
			$this->sdk->charges['ch_posted'] = $charge;
		}

		$result = $this->pay_with( $order, '' === $charge['chargeId'] ? 'ch_posted' : $charge['chargeId'] );

		$this->assertSame( 'failure', $result['result'] );
		$this->assertStringContainsString( 'could not be confirmed', $result['message'] );
		$failed = $this->reload( $order );
		$this->assertSame( 'failed', $failed->get_status() );
		$this->assertFalse( $failed->is_paid() );
		$this->assertSame( '', $failed->get_transaction_id() );
	}

	public function test_a_made_up_charge_id_fails_the_order() {
		$order = $this->order();

		$result = $this->pay_with( $order, 'ch' . str_repeat( '0', 30 ) );

		$this->assertSame( array( 'result' => 'failure', 'message' => 'Invalid ChargeId' ), $result );
		$this->assertFalse( $this->reload( $order )->is_paid() );
	}

	public function test_another_orders_charge_does_not_pay_a_second_order() {
		$first  = $this->order();
		$charge = $this->charge( $first );
		$this->assertSame( 'success', $this->pay_with( $first, $charge['chargeId'] )['result'] );

		$second = $this->order();
		$result = $this->pay_with( $second, $charge['chargeId'] );

		$this->assertSame( 'failure', $result['result'] );
		$this->assertFalse( $this->reload( $second )->is_paid() );
	}

	public function test_no_charge_id_fails_the_order_without_calling_payfurl() {
		$order = $this->order();

		$this->assertSame( 'failure', $this->pay_with( $order, '' )['result'] );
		$this->assertSame( array(), $this->sdk->calls );
	}

	public function test_a_pending_charge_puts_the_order_on_hold_unpaid() {
		$order  = $this->order( 'payfurl_azupay' );
		$charge = $this->charge( $order, array( 'status' => 'PENDING' ) );

		$result = $this->pay_with( $order, $charge['chargeId'], WC_Payfurl_PayId::class );

		$this->assertSame( 'success', $result['result'] );
		$held = $this->reload( $order );
		$this->assertSame( 'on-hold', $held->get_status() );
		$this->assertFalse( $held->is_paid() );
		$this->assertNull( $held->get_date_paid() );
		$this->assertSame( $charge['chargeId'], $held->get_meta( WC_Payfurl::META_CHARGE_ID ) );
	}

	public function test_a_card_charge_carries_the_order_number_as_its_reference() {
		add_filter( 'woocommerce_order_number', fn() => 'WC-0042' );
		$order = $this->order( 'payfurl_card' );
		$_POST = array( 'payfurl_token' => 'tok_card' );

		$result = ( new WC_Payfurl_Card() )->process_payment( $order->get_id() );

		$this->assertSame( 'success', $result['result'] );
		$request = $this->calls( 'charge_by_token' )[0][1];
		$this->assertSame( 'tok_card', $request['token'] );
		$this->assertSame( 'Order #WC-0042', $request['reference'] );
		$this->assertSame( 'WC-0042', $request['order']['orderNumber'] );
		$this->assertTrue( $this->reload( $order )->is_paid() );
	}
}
