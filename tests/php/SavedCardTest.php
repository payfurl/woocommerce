<?php
/**
 * PAY-5636: a saved card is charged only for its own customer. Token IDs are sequential, and 1.x charged any.
 *
 * @package PayfurlExtensionForWooCommerce
 */

class SavedCardTest extends Payfurl_Test_Case {

	private function saved_card( int $user_id, string $gateway = 'payfurl_card' ): WC_Payment_Token_CC {
		$token = new WC_Payment_Token_CC();
		$token->set_gateway_id( $gateway );
		$token->set_token( 'pm_of_user_' . $user_id );
		$token->set_user_id( $user_id );
		$token->set_last4( '1111' );
		$token->set_card_type( 'visa' );
		$token->set_expiry_month( '12' );
		$token->set_expiry_year( '2030' );
		$token->save();
		return $token;
	}

	private function pay_with_saved_card( WC_Order $order, int $token_id ): array {
		$_POST = array(
			'issavedtoken' => '1',
			'token'        => (string) $token_id,
		);
		return ( new WC_Payfurl_Card() )->process_payment( $order->get_id() );
	}

	public function test_a_customer_pays_with_their_own_saved_card() {
		$customer = self::factory()->user->create();
		$token    = $this->saved_card( $customer );
		wp_set_current_user( $customer );
		$order = $this->order( 'payfurl_card' );

		$result = $this->pay_with_saved_card( $order, $token->get_id() );

		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 'pm_of_user_' . $customer, $this->calls( 'charge_by_payment_method' )[0][1]['paymentMethodId'] );
		$this->assertTrue( $this->reload( $order )->is_paid() );
	}

	public function test_the_card_the_checkout_selects_at_load_is_charged() {
		// The Checkout block's state for the default saved card: WooCommerce's key, no isSavedToken.
		$customer = self::factory()->user->create();
		$token    = $this->saved_card( $customer );
		wp_set_current_user( $customer );
		$order = $this->order( 'payfurl_card' );
		$_POST = array(
			'token'                          => (string) $token->get_id(),
			'payment_method'                 => 'payfurl_card',
			'wc-payfurl_card-payment-token'  => (string) $token->get_id(),
		);

		$this->assertSame( 'success', ( new WC_Payfurl_Card() )->process_payment( $order->get_id() )['result'] );
		$this->assertSame( 'pm_of_user_' . $customer, $this->calls( 'charge_by_payment_method' )[0][1]['paymentMethodId'] );
	}

	public function test_another_customers_card_is_not_charged_under_woocommerces_key_either() {
		$token = $this->saved_card( self::factory()->user->create() );
		wp_set_current_user( self::factory()->user->create() );
		$_POST = array( 'wc-payfurl_card-payment-token' => (string) $token->get_id() );

		$this->assertSame( 'failure', ( new WC_Payfurl_Card() )->process_payment( $this->order( 'payfurl_card' )->get_id() )['result'] );
		$this->assertSame( array(), $this->sdk->calls );
	}

	public function test_another_customers_saved_card_is_not_charged() {
		$owner = self::factory()->user->create();
		$token = $this->saved_card( $owner );
		wp_set_current_user( self::factory()->user->create() );
		$order = $this->order( 'payfurl_card' );

		$result = $this->pay_with_saved_card( $order, $token->get_id() );

		$this->assertSame( 'failure', $result['result'] );
		$this->assertSame( array(), $this->sdk->calls );
		$this->assertFalse( $this->reload( $order )->is_paid() );
	}

	public function test_a_guest_cannot_pay_with_a_saved_card() {
		$token = $this->saved_card( self::factory()->user->create() );
		$order = $this->order( 'payfurl_card' );

		$this->assertSame( 'failure', $this->pay_with_saved_card( $order, $token->get_id() )['result'] );
		$this->assertSame( array(), $this->sdk->calls );
	}

	public function test_a_token_of_another_gateway_is_not_charged() {
		$customer = self::factory()->user->create();
		$token    = $this->saved_card( $customer, 'stripe' );
		wp_set_current_user( $customer );

		$this->assertSame( 'failure', $this->pay_with_saved_card( $this->order( 'payfurl_card' ), $token->get_id() )['result'] );
		$this->assertSame( array(), $this->sdk->calls );
	}

	public function test_a_card_saved_at_checkout_belongs_to_the_customer() {
		$customer = self::factory()->user->create();
		wp_set_current_user( $customer );
		$order = $this->order( 'payfurl_card' );
		$_POST = array(
			'payfurl_token'                      => 'tok_card',
			'wc-payfurl_card-new-payment-method' => '1',
		);

		$result = ( new WC_Payfurl_Card() )->process_payment( $order->get_id() );

		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 'pm_saved', $this->calls( 'charge_by_payment_method' )[0][1]['paymentMethodId'] );
		$tokens = WC_Payment_Tokens::get_customer_tokens( $customer, 'payfurl_card' );
		$this->assertCount( 1, $tokens );
		$token = reset( $tokens );
		$this->assertSame( 'pm_saved', $token->get_token() );
		$this->assertSame( '1111', $token->get_last4() );
		$this->assertSame( '2030', $token->get_expiry_year() );
	}
}
