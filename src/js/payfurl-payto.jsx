import { useEffect, useRef } from '@wordpress/element';
import { cartAmount, failureText, useShopperDetails } from './tools';

/**
 * The PayTo form. On Place Order the SDK gives a token, which the plugin charges on the server
 * (WC_Payfurl_PayTo). index.js mounts it again when the total changes: the SDK has no amount update for PayTo.
 *
 * @param {Object} props The block checkout's payment method props.
 */
export const PayfurlPayToComponent = ( props ) => {
	const { eventRegistration, emitResponse, billing, cartData } = props;
	const { onPaymentSetup } = eventRegistration;
	const added = useRef( false );

	useEffect( () => {
		if ( ! window._pf || added.current ) {
			return;
		}
		added.current = true;
		window._pf.addPayTo( 'payfurl-payto-component', cartAmount( billing ) );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

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

	return <div id="payfurl-payto-component"></div>;
};
