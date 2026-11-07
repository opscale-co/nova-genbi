let mix = require('laravel-mix')
let path = require('path')
let webpack = require('webpack')

mix
    .setPublicPath('dist')
    .js('resources/js/tool.js', 'js')
    .vue({ version: 3 })
    .css('resources/css/tool.css', 'css')
    .webpackConfig({
        externals: {
            vue: 'Vue',
            'laravel-nova': 'LaravelNova',
            'laravel-nova-ui': 'LaravelNovaUi',
        },
        output: {
            uniqueName: 'opscale-co/nova-genbi',
        },
        resolve: {
            symlinks: false,
            alias: { vue: path.resolve('./node_modules/vue') },
        },
        module: {
            // Nova serves a single script per package, so async chunks from
            // dynamic import() would 404. 'eager' inlines them in the bundle.
            parser: {
                javascript: {
                    dynamicImportMode: 'eager',
                },
            },
        },
        plugins: [
            new webpack.DefinePlugin({
                __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: 'false',
            }),
        ],
    })
    .options({
        vue: {
            exposeFilename: true,
        },
    })
