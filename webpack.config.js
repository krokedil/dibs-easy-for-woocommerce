const path = require('path');
module.exports = {
	mode: 'production', // production
	entry: {
		'checkout': './assets/js/checkout.js',
	},

	output: {
		filename: '[name].min.js',
		path: path.resolve(__dirname, './assets/js'),
	},
	devtool: 'source-map',
	module: {
		rules: [
			{
				test: /\.m?js$/,
				exclude: /(node_modules|bower_components)/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: ['@babel/preset-env'],
						plugins: ['@babel/plugin-proposal-object-rest-spread'],
					}
				}
			}
		],
	},
};