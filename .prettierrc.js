// Import the default config file and expose it in the project root.
// Useful for editor integrations.
const wpConfig = require( '@wordpress/prettier-config' );
const config = {
	...wpConfig,
	printWidth: 120,
};
module.exports = config;
