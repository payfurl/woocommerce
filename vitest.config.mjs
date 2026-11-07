// The unit tests' settings (wp-scripts test-unit-js). WooCommerce's packages are scripts WooCommerce loads,
// not npm packages (webpack.config.js): the tests get stubs.
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vitest/config';

const stub = ( name ) => fileURLToPath( new URL( `./src/js/test/${ name }-stub.js`, import.meta.url ) );

export default defineConfig( {
	resolve: {
		alias: {
			'@woocommerce/blocks-registry': stub( 'blocks-registry' ),
			'@woocommerce/settings': stub( 'settings' ),
		},
	},
	test: {
		environment: 'jsdom',
		include: [ 'src/**/*.test.js' ],
	},
} );
