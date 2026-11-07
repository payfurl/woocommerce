import { getSetting } from '@woocommerce/settings';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { PayfurlCardComponent } from './payfurl-card';
import { PayfurlPayToComponent } from './payfurl-payto';
import { PayfurlBrowserChargeComponent } from './payfurl-browser-charge';
import { isSafari } from './tools';

/**
 * Registers a checkout block payment method for each payFURL payment method the account can use for this cart
 * (`providersInfo`, from the API's /info/providers) and the plugin has a gateway for (`gateways`): a checkout
 * method without a gateway could be shown but not paid.
 *
 * @param {Object} settings `getSetting( 'payfurl_data' )`, from Payfurl_Blocks_Integration.
 * @return {string[]} The registered methods' names.
 */
export const registerPayfurlMethods = ( settings ) => {
	const info = settings?.providersInfo || {};
	const gateways = settings?.gateways || [];
	const registered = [];

	const register = ( name, label, Component, extra = {} ) => {
		if ( ! gateways.includes( name ) ) {
			return;
		}
		// Mounted again when the total changes: see PayfurlBrowserChargeComponent.
		const Content = ( props ) => (
			<Component
				key={
					extra.remountOnTotal ? props.billing?.cartTotal?.value : 0
				}
				{ ...props }
				{ ...extra.props }
			/>
		);
		registerPaymentMethod( {
			name,
			paymentMethodId: name,
			label: <strong>{ label }</strong>,
			ariaLabel: label,
			content: <Content />,
			edit: <div>{ label }</div>,
			canMakePayment: () => true,
			supports: {
				features: [ 'products' ],
				showSavedCards: !! extra.tokens,
				showSaveOption: !! extra.tokens,
			},
		} );
		registered.push( name );
	};

	const browserCharge = ( name, label, add ) =>
		register( name, label, PayfurlBrowserChargeComponent, {
			remountOnTotal: true,
			props: { elementId: name.replace( '_', '-' ) + '-component', add },
		} );

	if ( info.hasCardProviders ) {
		register(
			'payfurl_card',
			settings.title ||
				__(
					'Debit / Credit Card',
					'payfurl-extension-for-woocommerce'
				),
			PayfurlCardComponent,
			{ tokens: true }
		);
		// Loads Click to Pay's scripts ahead of the card form; it sends nothing.
		window._pf?.preloadClickToPay?.();
	}
	if ( info.hasPaypalProviders ) {
		browserCharge(
			'payfurl_paypal',
			'PayPal',
			( pf, id, amount, currency ) => pf.addPaypal( id, amount, currency )
		);
	}
	if ( info.hasPayToProviders ) {
		register( 'payfurl_payto', 'PayTo', PayfurlPayToComponent, {
			remountOnTotal: true,
		} );
	}
	if ( settings?.enable_googlepay && info.hasGooglePayProviders ) {
		browserCharge(
			'payfurl_googlepay',
			'Google Pay',
			( pf, id, amount, currency ) =>
				pf.addGooglePay( id, amount, currency )
		);
	}
	if (
		settings?.enable_applepay &&
		info.hasApplePayProviders &&
		isSafari()
	) {
		browserCharge(
			'payfurl_applepay',
			'Apple Pay',
			( pf, id, amount, currency ) =>
				pf.addApplePay( id, amount, currency )
		);
	}
	( info.bnplProviders || [] ).forEach( ( provider ) =>
		browserCharge(
			'payfurl_' + provider.providerType,
			checkoutTitle( provider.providerType ),
			( pf, id, amount, currency ) =>
				pf.addCheckout(
					id,
					amount,
					currency,
					provider.providerType,
					provider.providerId,
					{},
					provider.options
				)
		)
	);

	return registered;
};

const checkoutTitle = ( providerType ) =>
	( {
		azupay: 'PayID',
		pay_by_account: 'Pay by Account',
		upi: 'UPI',
		payglocal: 'PayGLocal',
	} )[ providerType ] ||
	providerType.charAt( 0 ).toUpperCase() + providerType.slice( 1 );

registerPayfurlMethods( getSetting( 'payfurl_data', {} ) );
