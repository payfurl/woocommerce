<?php
/**
 * The charge request the plugin sends for cards and PayTo.
 *
 * @package PayfurlExtensionForWooCommerce
 */

class ChargeRequestTest extends Payfurl_Test_Case {

	public function test_items_carry_what_was_paid_for_one_after_discount_and_its_tax() {
		$product = new WC_Product_Simple();
		$product->set_sku( 'tee-sku' );
		$product->set_regular_price( '50' );
		$product->save();
		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		// Two at 10.00, a 10% discount (18.00), 1.80 tax; the product's price has changed since to 50.
		$item->set_props(
			array(
				'product'   => $product,
				'name'      => 'payFURL Tee',
				'quantity'  => 2,
				'subtotal'  => 20,
				'total'     => 18,
				'total_tax' => 1.8,
			)
		);
		$order->add_item( $item );
		$order->set_shipping_total( 5 );
		$order->set_total( 24.8 );
		$order->save();

		$request = ( new WC_Payfurl_Card() )->charge_request( $order );

		$this->assertSame(
			array(
				array(
					'productCode' => 'tee-sku',
					'description' => 'payFURL Tee',
					'quantity'    => 2,
					'amount'      => 9.0,
					'taxAmount'   => 0.9,
				),
			),
			$request['order']['items']
		);
		$this->assertSame( '24.80', $request['amount'] );
		$this->assertSame( 'AUD', $request['currency'] );
		$this->assertSame( 'Order #' . $order->get_order_number(), $request['reference'] );
		$this->assertSame( '5', (string) $request['order']['freightAmount'] );
	}

	public function test_a_deleted_product_keeps_its_line() {
		$order      = $this->order( 'payfurl_card' );
		$product_id = current( $order->get_items() )->get_product_id();
		wp_delete_post( $product_id, true );

		$request = ( new WC_Payfurl_Card() )->charge_request( $this->reload( $order ) );

		$this->assertSame( '', $request['order']['items'][0]['productCode'] );
		$this->assertSame( 'payFURL Tee', $request['order']['items'][0]['description'] );
	}

	public function test_every_payment_method_shares_the_payfurl_settings() {
		$card   = new WC_Payfurl_Card();
		$paypal = new WC_Payfurl_Paypal();

		$this->assertSame( 'woocommerce_payfurl_settings', $card->get_option_key() );
		$this->assertSame( 'woocommerce_payfurl_settings', $paypal->get_option_key() );
		$this->assertSame( 'pk', $paypal->get_public_key() );
		// The card method has the configured title; the others their own name (1.x showed the card's for all).
		$this->assertSame( 'Credit or debit card', $card->get_title() );
		$this->assertSame( 'PayPal', $paypal->get_title() );
		$this->assertSame( 'PayID', ( new WC_Payfurl_PayId() )->get_title() );
	}

	public function test_woocommerces_payments_list_shows_one_payfurl_row() {
		// The list hides an extension's "shell" gateways (no admin title or description) behind its main one.
		$this->assertSame( 'payFURL', ( new WC_Payfurl() )->get_method_title() );
		foreach ( WC_Payfurl::PAYMENT_METHODS as $class ) {
			$gateway = new $class();
			$this->assertSame( '', $gateway->get_method_title(), $class );
			$this->assertSame( '', $gateway->get_method_description(), $class );
			$this->assertNotSame( '', $gateway->get_title(), $class );
		}
	}

	public function test_the_settings_gateway_is_never_offered_at_checkout() {
		$this->assertFalse( ( new WC_Payfurl() )->is_available() );
		$this->assertTrue( ( new WC_Payfurl_Card() )->is_available() );
	}
}
