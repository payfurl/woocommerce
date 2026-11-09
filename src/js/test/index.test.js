import { beforeEach, describe, expect, it, vi } from 'vitest';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { registerPayfurlMethods } from '../index';
import { cartAmount, failureText } from '../tools';

vi.mock( '@woocommerce/blocks-registry', () => ( {
	registerPaymentMethod: vi.fn(),
} ) );
vi.mock( '@woocommerce/settings', () => ( { getSetting: () => ( {} ) } ) );

const allGateways = [
	'payfurl_card',
	'payfurl_paypal',
	'payfurl_payto',
	'payfurl_googlepay',
	'payfurl_applepay',
	'payfurl_azupay',
	'payfurl_shift',
];

const settings = ( overrides = {} ) => ( {
	title: 'Credit or debit card',
	gateways: allGateways,
	enable_googlepay: true,
	enable_applepay: true,
	providersInfo: {
		hasCardProviders: true,
		hasPaypalProviders: true,
		hasPayToProviders: true,
		hasGooglePayProviders: true,
		hasApplePayProviders: true,
		bnplProviders: [
			{ providerType: 'azupay', providerId: 'p1' },
			{ providerType: 'zip', providerId: 'p2' },
		],
	},
	...overrides,
} );

beforeEach( () => {
	registerPaymentMethod.mockClear();
	window._pf = { preloadClickToPay: vi.fn() };
} );

describe( 'registerPayfurlMethods', () => {
	it( 'registers each method the account has and the plugin has a gateway for', () => {
		// Zip is offered by the API, but the plugin has no gateway to pay it with (1.x showed it, then failed).
		// Apple Pay only in Safari.
		expect( registerPayfurlMethods( settings() ) ).toEqual( [
			'payfurl_card',
			'payfurl_paypal',
			'payfurl_payto',
			'payfurl_googlepay',
			'payfurl_azupay',
		] );
	} );

	it( 'registers only the card method with saved cards, and preloads Click to Pay for it', () => {
		registerPayfurlMethods( settings() );

		const card = registerPaymentMethod.mock.calls.find(
			( [ config ] ) => config.name === 'payfurl_card'
		)[ 0 ];
		expect( card.supports ).toEqual( {
			features: [ 'products' ],
			showSavedCards: true,
			showSaveOption: true,
		} );
		expect( card.savedTokenComponent ).toBeUndefined();
		expect( window._pf.preloadClickToPay ).toHaveBeenCalled();
		const paypal = registerPaymentMethod.mock.calls.find(
			( [ config ] ) => config.name === 'payfurl_paypal'
		)[ 0 ];
		expect( paypal.supports.showSavedCards ).toBe( false );
	} );

	it( 'leaves out wallets the merchant turned off', () => {
		expect(
			registerPayfurlMethods( settings( { enable_googlepay: false } ) )
		).not.toContain( 'payfurl_googlepay' );
	} );

	it( "registers nothing without the account's providers (the block editor)", () => {
		expect(
			registerPayfurlMethods( settings( { providersInfo: {} } ) )
		).toEqual( [] );
		expect( registerPaymentMethod ).not.toHaveBeenCalled();
	} );
} );

describe( 'tools', () => {
	it( 'converts the cart total from minor units', () => {
		expect(
			cartAmount( {
				cartTotal: { value: '3050' },
				currency: { minorUnit: 2 },
			} )
		).toBe( 30.5 );
		expect(
			cartAmount( {
				cartTotal: { value: '3050' },
				currency: { minorUnit: 0 },
			} )
		).toBe( 3050 );
	} );

	it( 'reads the SDK failure text', () => {
		expect( failureText( { error: 'Card declined' } ) ).toBe(
			'Card declined'
		);
		expect( failureText( 'Timed out' ) ).toBe( 'Timed out' );
		expect( failureText( {} ) ).toBe(
			'The payment failed. Please try again.'
		);
	} );
} );
