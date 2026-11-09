import { useEffect, useRef } from '@wordpress/element';
import { cartAmount, failureText, useShopperDetails } from './tools';

/**
 * The card form, with Click to Pay. The block checkout mounts it only while the card method is selected, so
 * Click to Pay starts only then. On Place Order the SDK tokenises the card; the plugin charges the token on the
 * server (WC_Payfurl_Card).
 *
 * @param {Object} props The block checkout's payment method props.
 */
export const PayfurlCardComponent = ( props ) => {
	const { eventRegistration, emitResponse, billing, cartData } = props;
	const { onPaymentSetup } = eventRegistration;
	const amount = cartAmount( billing );
	const added = useRef( false );

	useEffect( () => {
		if ( ! window._pf || added.current ) {
			return;
		}
		added.current = true;
		window._pf.addCardLeastCost(
			'payfurl-card-component',
			amount,
			billing.currency.code,
			{
				style: 'compact',
				threeDSEmail: billing?.billingData?.email,
				createNetworkToken: false,
				tokenAuthenticationEnabled: false,
				clickToPayEnabled: true,
			}
		);
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// A coupon or a shipping method changes the total after the form loads.
	useEffect( () => {
		window._pf?.setAmount( amount );
	}, [ amount ] );

	useShopperDetails( billing, cartData );

	useEffect(
		() =>
			onPaymentSetup(
				() =>
					new Promise( ( resolve ) => {
						window._pf.onFailure( ( message ) =>
							resolve( {
								type: emitResponse.responseTypes.ERROR,
								message: failureText( message ),
							} )
						);
						window._pf.onSuccess( ( message ) =>
							resolve( {
								type: emitResponse.responseTypes.SUCCESS,
								meta: {
									paymentMethodData: {
										payfurl_token: message.token,
									},
								},
							} )
						);
						window._pf.execute();
					} )
			),
		[ onPaymentSetup, emitResponse.responseTypes ]
	);

	return <div id="payfurl-card-component"></div>;
};
