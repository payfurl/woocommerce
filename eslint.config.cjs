// wp-scripts' lint rules. WooCommerce's packages are scripts WooCommerce loads, not npm dependencies
// (webpack.config.js maps them).
const wpScripts = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...wpScripts,
	{
		settings: {
			'import/core-modules': [ '@woocommerce/settings', '@woocommerce/blocks-registry' ],
		},
	},
];
