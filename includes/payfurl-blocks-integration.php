<?php
/**
 * The checkout block's payFURL payment methods.
 *
 * @package PayfurlExtensionForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Loads the browser SDK and build/index.js, which registers one checkout block payment method per payFURL gateway
 * the account can use (src/js/index.js). Their data is `getSetting( 'payfurl_data' )`.
 */
class Payfurl_Blocks_Integration extends AbstractPaymentMethodType {

	/**
	 * The integration's name, and the settings key of its data.
	 *
	 * @var string
	 */
	protected $name = 'payfurl';

	/**
	 * The card payment method, for the shared settings and whether payFURL is offered here.
	 *
	 * @var WC_Payfurl
	 */
	protected $gateway;

	/**
	 * Set up.
	 */
	public function initialize() {
		$this->gateway  = new WC_Payfurl_Card();
		$this->settings = $this->gateway->settings;
	}

	/**
	 * Whether payFURL is enabled and offered here.
	 *
	 * @return bool
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * The SDK (`payfurl`, set up as `window._pf`) and the payment methods (`payfurl_checkout`).
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {
		/**
		 * The browser SDK's URL, a versioned assets.payfurl.com release by default.
		 *
		 * @since 2.0.0
		 * @param string $url The URL.
		 */
		$sdk_url = apply_filters( 'payfurl_sdk_url', PAYFURL_SDK_URL );
		// The URL carries the SDK's version.
		wp_register_script( 'payfurl', $sdk_url, array(), null, false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_add_inline_script(
			'payfurl',
			sprintf(
				'window._pf = window.payfurl ? window.payfurl.init(%s, %s, %s) : undefined;',
				wp_json_encode( $this->gateway->get_option( 'environment', 'sandbox' ) ),
				wp_json_encode( $this->gateway->get_public_key() ),
				wp_json_encode( 'yes' === $this->gateway->get_option( 'debug' ) )
			)
		);

		$asset = require plugin_dir_path( PAYFURL_PLUGIN_FILE ) . 'build/index.asset.php';
		wp_register_script(
			'payfurl_checkout',
			plugins_url( 'build/index.js', PAYFURL_PLUGIN_FILE ),
			array_merge( $asset['dependencies'], array( 'payfurl' ) ),
			$asset['version'],
			true
		);
		wp_set_script_translations( 'payfurl_checkout', 'payfurl-extension-for-woocommerce' );

		return array( 'payfurl_checkout' );
	}

	/**
	 * What the payment methods need: the card method's title, the wallet toggles, the account's payment methods
	 * for the cart (`/info/providers`) and the payFURL gateways that exist.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$cart = WC()->cart instanceof WC_Cart ? WC()->cart : null;
		return array(
			'title'            => $this->gateway->get_option( 'title' ),
			// The block editor has no cart: it shows no payFURL methods rather than call the API.
			'providersInfo'    => $cart ? WC_Payfurl_SDK::get_instance()->get_providers_info( $cart->get_total( 'edit' ), get_woocommerce_currency() ) : array(),
			'enable_googlepay' => 'yes' === $this->gateway->get_option( 'enable_googlepay' ),
			'enable_applepay'  => 'yes' === $this->gateway->get_option( 'enable_applepay' ),
			'gateways'         => array_values(
				array_filter(
					array_keys( WC()->payment_gateways()->payment_gateways() ),
					fn( $id ) => str_starts_with( $id, 'payfurl_' )
				)
			),
		);
	}
}
