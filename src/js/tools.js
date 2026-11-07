import { useEffect, useRef } from '@wordpress/element';

/**
 * The cart total in the currency's major unit: the block checkout gives it in minor units (cents).
 *
 * @param {Object} billing The payment method's `billing` prop.
 * @return {number} The amount.
 */
export const cartAmount = ( billing ) =>
	Number( billing?.cartTotal?.value || 0 ) /
	10 ** ( billing?.currency?.minorUnit ?? 2 );

const COMPLETE_EMAIL = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;

const minorToMajor = ( value, billing ) =>
	Number( value || 0 ) / 10 ** ( billing?.currency?.minorUnit ?? 2 );

const totalOf = ( key, billing ) =>
	minorToMajor(
		( billing?.cartTotalItems || [] ).find( ( item ) => item.key === key )
			?.value,
		billing
	);

/**
 * The text of the SDK's onFailure message.
 *
 * @param {Object|string} message The SDK's failure.
 * @return {string} The text.
 */
export const failureText = ( message ) =>
	( typeof message === 'string' ? message : message?.error ) ||
	'The payment failed. Please try again.';

/**
 * Sends the shopper's details and the order to the SDK, from this one place. The details are read as they
 * change, not once when the form loads: a shopper often types their email after the payment form appears.
 * The customer (whose email starts a Click to Pay lookup) is sent only when it changes to a complete email (or
 * none), a second after the shopper stops typing: browser SDK 4.10.0 looks up each new email it is given, and
 * a passcode goes to every known one (PAY-5627).
 *
 * @param {Object} billing  The payment method's `billing` prop.
 * @param {Object} cartData The payment method's `cartData` prop.
 */
export const useShopperDetails = ( billing, cartData ) => {
	const sentCustomer = useRef( null );
	const data = billing?.billingData || {};
	const customer = {
		firstName: data.first_name,
		lastName: data.last_name,
		email: data.email,
		phoneNumber: data.phone,
	};
	const emailComplete = ! data.email || COMPLETE_EMAIL.test( data.email );
	const customerKey = JSON.stringify( customer );
	const orderKey = JSON.stringify( [
		data,
		billing?.cartTotalItems,
		cartData?.cartItems,
	] );

	useEffect( () => {
		const send = () => {
			const pf = window._pf;
			if ( ! pf ) {
				return;
			}
			if ( emailComplete && sentCustomer.current !== customerKey ) {
				sentCustomer.current = customerKey;
				pf.setCustomerInfo( customer );
			}
			pf.setBillingAddress( {
				...customer,
				email: emailComplete ? data.email : undefined,
				streetAddress: data.address_1,
				streetAddress2: data.address_2,
				city: data.city,
				state: data.state,
				postalCode: data.postcode,
				country: data.country,
			} ).setOrderInfo( {
				taxAmount: totalOf( 'total_tax', billing ),
				shippingAmount: totalOf( 'total_shipping', billing ),
				discountAmount: totalOf( 'total_discount', billing ),
				orderItems: ( cartData?.cartItems || [] ).map( ( item ) => ( {
					name: item.name,
					quantity: item.quantity || 1,
					sku: item.sku,
					unitPrice: minorToMajor( item.prices?.price, billing ),
					totalAmount: minorToMajor(
						item.totals?.line_total,
						billing
					),
				} ) ),
			} );
		};
		// The first details go at once; later changes after the shopper pauses.
		const timer = setTimeout( send, sentCustomer.current ? 1000 : 0 );
		return () => clearTimeout( timer );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ customerKey, orderKey ] );
};

const setPlaceOrderVisible = ( visible ) => {
	document
		.querySelectorAll( '.wc-block-components-checkout-place-order-button' )
		.forEach( ( button ) => {
			button.style.display = visible ? '' : 'none';
		} );
};

/**
 * Hides Place Order while the method is selected: its own button (PayPal, a wallet, a checkout) pays and then
 * submits the checkout.
 */
export const useHiddenPlaceOrder = () =>
	useEffect( () => {
		setPlaceOrderVisible( false );
		return () => setPlaceOrderVisible( true );
	}, [] );

export const isSafari = () =>
	/^((?!chrome|android).)*safari/i.test( window.navigator.userAgent );
