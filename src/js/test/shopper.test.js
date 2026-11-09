import { createElement, createRoot } from '@wordpress/element';
// eslint-disable-next-line import/no-extraneous-dependencies -- @wordpress/element's React.
import { act } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useShopperDetails } from '../tools';

const billing = ( email ) => ( {
	billingData: { email, first_name: 'John', last_name: 'Doe' },
	cartTotalItems: [],
	currency: { minorUnit: 2 },
} );

const Shopper = ( { email } ) => {
	useShopperDetails( billing( email ), { cartItems: [] } );
	return null;
};

describe( 'useShopperDetails', () => {
	let root;
	beforeEach( () => {
		vi.useFakeTimers();
		globalThis.IS_REACT_ACT_ENVIRONMENT = true;
		const pf = {
			setCustomerInfo: vi.fn(),
			setBillingAddress: vi.fn( () => pf ),
			setOrderInfo: vi.fn( () => pf ),
		};
		window._pf = pf;
		root = createRoot( document.createElement( 'div' ) );
	} );
	afterEach( () => {
		act( () => root.unmount() );
		vi.useRealTimers();
	} );

	const type = ( email ) =>
		act( () => {
			root.render( createElement( Shopper, { email } ) );
		} );

	it( 'sends each complete email once, after the shopper pauses (each one is a Click to Pay lookup)', () => {
		type( '' );
		act( () => vi.advanceTimersByTime( 0 ) );
		expect( window._pf.setCustomerInfo ).toHaveBeenCalledTimes( 1 );

		for ( const partial of [
			'j',
			'jo',
			'john@',
			'john@example',
			'john@example.c',
		] ) {
			type( partial );
			act( () => vi.advanceTimersByTime( 300 ) );
		}
		act( () => vi.advanceTimersByTime( 1000 ) );
		// john@example.c is complete enough to look like an email: sent once, after the pause.
		expect( window._pf.setCustomerInfo ).toHaveBeenCalledTimes( 2 );

		type( 'john@example.com' );
		act( () => vi.advanceTimersByTime( 1000 ) );
		type( 'john@example.com' );
		act( () => vi.advanceTimersByTime( 1000 ) );
		expect( window._pf.setCustomerInfo ).toHaveBeenCalledTimes( 3 );
		expect( window._pf.setCustomerInfo ).toHaveBeenLastCalledWith(
			expect.objectContaining( { email: 'john@example.com' } )
		);
	} );

	it( 'never sends an incomplete email', () => {
		type( 'john@exa' );
		act( () => vi.advanceTimersByTime( 5000 ) );
		expect( window._pf.setCustomerInfo ).not.toHaveBeenCalled();
		expect( window._pf.setBillingAddress ).toHaveBeenLastCalledWith(
			expect.objectContaining( { email: undefined } )
		);
	} );
} );
