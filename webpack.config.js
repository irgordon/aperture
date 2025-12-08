const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'admin-app': path.resolve( process.cwd(), 'src', 'admin', 'index.js' ),
		'frontend-form': path.resolve( process.cwd(), 'src', 'frontend', 'index.js' ),
		'client-portal': path.resolve( process.cwd(), 'src', 'frontend', 'PortalApp.js' ),
	},
	output: {
		path: path.resolve( process.cwd(), 'assets', 'build' ),
		filename: '[name].js',
	},
};
