const mix = require('laravel-mix');
require('laravel-mix-clean');
require('./scripts/clean-emit.js');

const TerserPlugin = require('terser-webpack-plugin');

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
mix.js('resources/js/app.js', 'public/js')
    .vue();

mix.postCss('resources/css/app.pcss', 'public/css', [
    require('postcss-import'),
    require('tailwindcss'),
]);

mix.copy('resources/css/all.min.css', 'public/css/all.min.css');

// Extract & cleanup.
mix.extract();
mix.clean({
    protectWebpackAssets: false,

    cleanOnceBeforeBuildPatterns: [
        'css/*',
        'js/*',
    ],
});
mix.cleanEmit({
    remove: [
        /\.LICENSE\.txt$/m,
    ],
    stripComments: true,
});

// Config.
mix.webpackConfig({
    output: {
        chunkFilename: fileHash,
    },
    devtool: false,
    optimization: {
        minimize: mix.inProduction(),
        minimizer: [
            new TerserPlugin({
                minify: TerserPlugin.swcMinify,
                extractComments: false,
            }),
        ],
    },
});

function fileHash(chunk) {
    const hash = chunk.chunk.hash.slice(0, 8);

    return `js/chunk.${hash}.js`;
}
