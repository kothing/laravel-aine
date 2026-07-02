const mix = require('laravel-mix');
// const tailwindcss = require('tailwindcss');
const webpack = require('webpack');
require('dotenv').config();

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */
mix.options({
    terser: {
        extractComments: false,
    }
});

mix.js('resources/js/app.js', 'public/js').vue()
    .js('resources/js/admin.js', 'public/js').vue()
    .js('resources/js/form.js', 'public/js').vue()
    .postCss('resources/css/admin.css', 'public/css', [
        require('tailwindcss')('./tailwind.config.js')
    ])
    .postCss('resources/css/app.css', 'public/css', [
        require('tailwindcss')('./tailwind.config.js')
    ])
    .copy('node_modules/tinymce/skins', 'public/js/tinymce/skins')
    .webpackConfig({
        output: {
            chunkFilename: 'js/partials/[name].js?id=[hash]',
        },
        plugins: []
    }).version();
