import { createElement, createRoot } from '@wordpress/element';
// eslint-disable-next-line import/no-extraneous-dependencies -- @wordpress/element's React.
import { act } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { PayfurlBrowserChargeComponent } from '../payfurl-browser-charge';

const responseTypes = { SUCCESS: 'success', ERROR: 'error' };

describe( 'PayfurlBrowserChargeComponent', () => {
	let root, sdk, setup, props;
	beforeEach( () => {
		globalThis.IS_REACT_ACT_ENVIRONMENT = true;
		document.body.innerHTML =
			'<button class="wc-block-components-checkout-place-order-button">Place Order</button><div id="root"></div>';
		sdk = { handlers: {} };
		sdk.onSuccess = ( f ) => ( ( sdk.handlers.success = f ), sdk );
		sdk.onFailure = ( f ) => ( ( sdk.handlers.failure = f ), sdk );
		window._pf = {
			setCustomerInfo: vi.fn(),
			setBillingAddress: vi.fn( () => window._pf ),
			setOrderInfo: vi.fn( () => window._pf ),
		};
		props = {
			elementId: 'payfurl-paypal-component',
			add: vi.fn( () => sdk ),
			onSubmit: vi.fn(),
			eventRegistration: {
				onPaymentSetup: vi.fn( ( f ) => {
					setup = f;
					return () => {};
				} ),
			},
			emitResponse: { responseTypes },
			billing: {
				cartTotal: { value: '3000' },
				currency: { code: 'AUD', minorUnit: 2 },
				billingData: {},
			},
			cartData: { cartItems: [] },
		};
		root = createRoot( document.getElementById( 'root' ) );
		act( () =>
			root.render( createElement( PayfurlBrowserChargeComponent, props ) )
		);
	} );
	afterEach( () => act( () => root.unmount() ) );

	const placeOrder = () =>
		document.querySelector(
			'.wc-block-components-checkout-place-order-button'
		);

	it( 'adds the method for the cart total and hides Place Order while selected', () => {
		expect( props.add ).toHaveBeenCalledWith(
			window._pf,
			'payfurl-paypal-component',
			30,
			'AUD'
		);
		expect( placeOrder().style.display ).toBe( 'none' );
		act( () => root.unmount() );
		expect( placeOrder().style.display ).toBe( '' );
		root = createRoot( document.getElementById( 'root' ) );
	} );

	it( 'refuses to pay before the method has charged', () => {
		expect( setup() ).toEqual( {
			type: 'error',
			message: 'Complete the payment with the button above.',
		} );
		expect( props.onSubmit ).not.toHaveBeenCalled();
	} );

	it( 'submits the checkout with the charge ID once the method has charged', () => {
		act( () => sdk.handlers.success( { transactionId: 'ch_paypal' } ) );

		expect( props.onSubmit ).toHaveBeenCalledTimes( 1 );
		expect( setup() ).toEqual( {
			type: 'success',
			meta: {
				paymentMethodData: { payfurl_transaction_id: 'ch_paypal' },
			},
		} );
	} );

	it( "shows the method's failure", () => {
		act( () => sdk.handlers.failure( { error: 'PayPal declined' } ) );

		expect( document.querySelector( '[role="alert"]' ).textContent ).toBe(
			'PayPal declined'
		);
		expect( props.onSubmit ).not.toHaveBeenCalled();
	} );
} );
