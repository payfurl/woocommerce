<?php
/**
 * PAY-5649: WC_Payfurl_SDK on payfurl/phpsdk 3, through the SDK's fake transport: the key and host for the configured
 * environment, the requests it builds from the plugin's arrays, and API errors as `errorMessage`/`errorCode`.
 *
 * @package PayfurlExtensionForWooCommerce
 */

use payFURL\Sdk\ClientOptions;
use payFURL\Sdk\Http\FakeTransport;

class SdkTest extends Payfurl_Test_Case {

	/** @var FakeTransport */
	private $transport;

	public function set_up(): void {
		parent::set_up();
		$this->transport = new FakeTransport();
		update_option(
			'woocommerce_payfurl_settings',
			array(
				'environment'            => 'sandbox',
				'sandbox_private_key'    => 'sectest-sandbox-au',
				'production_private_key' => 'seclive-production-au',
			)
		);
	}

	private function sdk(): WC_Payfurl_SDK {
		return new WC_Payfurl_SDK( new ClientOptions( maxRetries: 0, transport: $this->transport ) );
	}

	public function test_the_sandbox_key_and_host() {
		$this->transport->queue( 200, array( 'chargeId' => 'ch_1', 'status' => 'SUCCESS' ) );

		$this->assertSame( array( 'chargeId' => 'ch_1', 'status' => 'SUCCESS' ), $this->sdk()->get_charge( 'ch_1' ) );

		$request = $this->transport->lastRequest();
		$this->assertSame( 'https://sandbox-api-au.payfurl.com/charge/ch_1', $request->url );
		$this->assertSame( 'sectest-sandbox-au', $request->headers['x-secretkey'] );
	}

	public function test_the_production_key_and_host() {
		update_option(
			'woocommerce_payfurl_settings',
			array(
				'environment'            => 'production',
				'sandbox_private_key'    => 'sectest-sandbox-au',
				'production_private_key' => 'seclive-production-au',
			)
		);
		$this->transport->queue( 200, array( 'chargeId' => 'ch_1' ) );

		$this->sdk()->get_charge( 'ch_1' );

		$this->assertSame( 'https://api-au.payfurl.com/charge/ch_1', $this->transport->lastRequest()->url );
		$this->assertSame( 'seclive-production-au', $this->transport->lastRequest()->headers['x-secretkey'] );
	}

	public function test_a_charge_request_from_the_order() {
		$order   = $this->order( 'payfurl_card', 25.0 );
		$gateway = new WC_Payfurl_Card();
		$request = $gateway->charge_request( $order );
		$this->transport->queue( 201, array( 'chargeId' => 'ch_1', 'status' => 'SUCCESS' ) );

		$this->sdk()->charge_by_token( $request + array( 'token' => 'tok_1' ) );

		$this->assertSame( 'https://sandbox-api-au.payfurl.com/charge/token', $this->transport->lastRequest()->url );
		$body = $this->transport->lastBody();
		$this->assertSame( 'tok_1', $body['token'] );
		$this->assertSame( 25.0, $body['amount'] );
		$this->assertSame( 'AUD', $body['currency'] );
		$this->assertSame( WC_Payfurl::reference( $order ), $body['reference'] );
		$this->assertSame( 'shopper@example.com', $body['email'] );
		$this->assertSame( (string) $order->get_order_number(), $body['order']['orderNumber'] );
		$this->assertSame( 1, $body['order']['items'][0]['quantity'] );
		$this->assertSame( 25.0, $body['order']['items'][0]['amount'] );
	}

	public function test_a_saved_payment_method_and_its_charge() {
		$this->transport->queue( 201, array( 'paymentMethodId' => 'pm_1' ) )->queue( 201, array( 'chargeId' => 'ch_1' ) );
		$sdk = $this->sdk();

		$this->assertSame( array( 'paymentMethodId' => 'pm_1' ), $sdk->create_payment_method( 'tok_1' ) );
		$this->assertSame( array( 'token' => 'tok_1' ), $this->transport->lastBody() );
		$this->assertSame( 'https://sandbox-api-au.payfurl.com/payment_method/token', $this->transport->lastRequest()->url );

		$sdk->charge_by_payment_method(
			array(
				'amount'          => '10.00',
				'currency'        => 'AUD',
				'paymentMethodId' => 'pm_1',
			)
		);
		$this->assertSame(
			array(
				'amount'          => 10.0,
				'paymentMethodId' => 'pm_1',
				'currency'        => 'AUD',
			),
			$this->transport->lastBody()
		);
	}

	public function test_a_refund() {
		$this->transport->queue( 200, array( 'status' => 'PARTIAL_REFUND' ) );

		$this->sdk()->refund( 'ch_1', 5.5, 'Damaged' );

		$this->assertSame( 'DELETE', $this->transport->lastRequest()->method );
		$this->assertSame( 'https://sandbox-api-au.payfurl.com/charge/ch_1?amount=5.5&comment=Damaged', $this->transport->lastRequest()->url );
	}

	public function test_providers_are_asked_for_with_the_browser_sdk_version() {
		$this->transport->queue( 200, array( 'hasPaypalProviders' => true ) );

		$this->assertSame( array( 'hasPaypalProviders' => true ), $this->sdk()->get_providers_info( '25.00', 'AUD' ) );

		$request = $this->transport->lastRequest();
		$this->assertSame( 'https://sandbox-api-au.payfurl.com/info/providers?amount=25&currency=AUD', $request->url );
		$this->assertSame( WC_Payfurl_SDK::browser_sdk_version(), $request->headers['sdk-version'] );
		$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+$/', (string) WC_Payfurl_SDK::browser_sdk_version() );
	}

	public function test_an_api_error_is_returned_not_thrown() {
		$this->transport->queue(
			400,
			array(
				'message' => 'Card declined',
				'code'    => 103,
			)
		);

		$result = $this->sdk()->charge_by_token(
			array(
				'amount' => 10,
				'token'  => 'tok_1',
			)
		);

		$this->assertSame(
			array(
				'errorMessage' => 'Card declined',
				'errorCode'    => 103,
			),
			$result
		);
	}

	public function test_a_network_error_is_returned_not_thrown() {
		$this->transport->queueFailure( true );

		$result = $this->sdk()->get_charge( 'ch_1' );

		$this->assertSame( 'Operation timed out', $result['errorMessage'] );
		$this->assertSame( 0, $result['errorCode'] );
	}

	public function test_no_private_key_is_an_error_without_a_request() {
		update_option( 'woocommerce_payfurl_settings', array( 'environment' => 'sandbox' ) );

		$result = $this->sdk()->get_charge( 'ch_1' );

		$this->assertSame( 0, $result['errorCode'] );
		$this->assertSame( array(), $this->transport->requests );
	}
}
