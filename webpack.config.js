const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        'blocks': path.resolve(__dirname, 'assets/js/blocks.js'),
        'frontend': path.resolve(__dirname, 'assets/js/frontend.js'),
        'admin': path.resolve(__dirname, 'assets/js/admin.js'),
        'event-single': path.resolve(__dirname, 'assets/js/event-single.js'),
        'event-page': path.resolve(__dirname, 'assets/js/event-page.js')
    },
    output: {
        path: path.resolve(__dirname, 'build'),
        filename: '[name].js'
    },
    externals: {
        ...defaultConfig.externals,
        jquery: 'jQuery',
        '@wordpress/element': 'wp.element',
        '@wordpress/components': 'wp.components',
        '@wordpress/i18n': 'wp.i18n'
    },
    module: {
        ...defaultConfig.module,
        rules: [
            ...defaultConfig.module.rules,
            {
                test: /\.svg$/,
                use: ['@svgr/webpack', 'url-loader']
            }
        ]
    }
}; 