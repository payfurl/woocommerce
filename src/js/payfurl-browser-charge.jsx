import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	cartAmount,
	failureText,
	useHiddenPlaceOrder,
	useShopperDetails,
} from './tools';

/**
 * PayPal, Google Pay, Apple Pay and the checkout methods (PayID, Shift, ...): the SDK shows the method's own
 * button, which creates the charge in the browser, then submits the checkout with the charge ID. The ID is only a
 * claim: the plugin fetches the charge and checks it pays this order (WC_Payfurl_Checkout). index.js mounts the
 * component again when the total changes, so the button always charges the current total.
 *
 * @param {Object}                                                                      props           The block checkout's payment method props.
 * @param {string}                                                                      props.elementId The id of the element the SDK renders into.
 * @param {(pf: Object, elementId: string, amount: number, currency: string) => Object} props.add       Adds the method to the SDK.
 */
export const PayfurlBrowserChargeComponent = ( props ) => {
	const {
		elementId,
		add,
		eventRegistration,
		emitResponse,
		billing,
		cartData,
	} = props;
	const { onPaymentSetup } = eventRegistration;
	const chargeId = useRef( '' );
	const added = useRef( false );
	const submit = useRef( props.onSubmit );
	submit.current = props.onSubmit;
	const [ error, setError ] = useState( '' );

	useHiddenPlaceOrder();

	useEffect( () => {
		if ( ! window._pf || added.current ) {
			return;
		}
		added.current = true;
		add(
			window._pf,
			elementId,
			cartAmount( billing ),
			billing.currency.code
		)
			.onFailure( ( message ) => setError( failureText( message ) ) )
			.onSuccess( ( message ) => {
				chargeId.current = message.transactionId;
				setError( '' );
				submit.current();
			} );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	useShopperDetails( billing, cartData );

	useEffect(
		() =>
			onPaymentSetup( () =>
				chargeId.current
					? {
							type: emitResponse.responseTypes.SUCCESS,
							meta: {
								paymentMethodData: {
									payfurl_transaction_id: chargeId.current,
								},
							},
						}
					: {
							type: emitResponse.responseTypes.ERROR,
							message: __(
								'Complete the payment with the button above.',
								'payfurl-extension-for-woocommerce'
							),
						}
			),
		[ onPaymentSetup, emitResponse.responseTypes ]
	);

	return (
		<>
			<div id={ elementId }></div>
			{ error && (
				<div
					className="wc-block-components-validation-error"
					role="alert"
				>
					<p>{ error }</p>
				</div>
			) }
		</>
	);
};
