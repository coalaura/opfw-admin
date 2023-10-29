const mix = require('laravel-mix');

const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');

const WebpackCleanupPlugin = require('./scripts/cleanup.js');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

// Disable notifications.
mix.disableNotifications();

// Version & source maps.
mix.version();
mix.sourceMaps(false);

// Assets.
mix.js('resources/js/app.js', 'public/js').vue();
mix.postCss('resources/css/app.pcss', 'public/css', [
    require('postcss-import'),
    require('tailwindcss'),
]);

// Config.
mix.webpackConfig({
    output: {
        chunkFilename: 'js/[name].js?id=[chunkhash]',
    },
    devtool: false,
    plugins: [
        new CleanWebpackPlugin({
            cleanOnceBeforeBuildPatterns: ['public/js/*', 'public/css/*'],
        }),
        new WebpackCleanupPlugin(),
    ],
    optimization: {
        minimize: mix.inProduction(),
        minimizer: [
            new TerserPlugin({
                extractComments: false,
            }),
        ],
    },
});
