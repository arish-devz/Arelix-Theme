const path = require('path');
const webpack = require('webpack');
const AssetsManifestPlugin = require('webpack-assets-manifest');
const ForkTsCheckerWebpackPlugin = require('fork-ts-checker-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;
const MonacoWebpackPlugin = require('monaco-editor-webpack-plugin');

const isProduction = process.env.NODE_ENV === 'production';

const isNode18 = (() => {
    try {
        const v = process.versions?.node || process.version || '';
        const major = parseInt((v.replace('v', '').split('.') || ['0'])[0], 10);
        return major >= 18;
    } catch {
        return false;
    }
})();
const isWatch = process.argv.includes('--watch');
const disableTsChecker = !isProduction && isNode18 && isWatch && process.env.FORCE_TS_CHECKER !== '1';

module.exports = {
    cache: true,
    target: 'web',
    mode: process.env.NODE_ENV,
    devtool: isProduction ? false : (process.env.DEVTOOL || 'eval-source-map'),
    performance: {
        hints: false,
    },
    entry: ['react-hot-loader/patch', './resources/scripts/index.tsx'],
    output: {
        path: path.join(__dirname, '/public/assets'),
        filename: isProduction ? 'bundle.[chunkhash:8].js' : 'bundle.[hash:8].js',
        chunkFilename: isProduction ? '[name].[chunkhash:8].js' : '[name].[hash:8].js',
        publicPath: (process.env.WEBPACK_PUBLIC_PATH || '/assets/'),
        crossOriginLoading: 'anonymous',
    },
    module: {
        rules: [
            {
                test: /\.tsx?$/,
                exclude: /node_modules(?!\/ogl)|\.spec\.tsx?$/,
                loader: 'babel-loader',
            },
            {
                test: /\.js$/,
                include: /node_modules\/(ogl|tailwind-merge|motion|framer-motion|motion-dom)/,
                loader: 'babel-loader',
            },
            {
                test: /\.mjs$/,
                include: /node_modules/,
                type: 'javascript/auto',
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: [
                            ['@babel/preset-env', {
                                modules: false,
                                useBuiltIns: 'entry',
                                corejs: 3,
                                targets: {
                                    browsers: ['> 0.5%', 'last 2 versions', 'firefox esr', 'not dead']
                                }
                            }]
                        ],
                        plugins: [
                            '@babel/plugin-proposal-optional-chaining',
                            '@babel/plugin-proposal-nullish-coalescing-operator'
                        ]
                    }
                }
            },
            {
                test: /\.m?js$/,
                include: /node_modules\/@?monaco-editor/,
                type: 'javascript/auto',
                loader: 'esbuild-loader',
            },
            {
                test: /\.css$/,
                use: [
                    { loader: 'style-loader' },
                    {
                        loader: 'css-loader',
                        options: {
                            modules: {
                                auto: true,
                                localIdentName: isProduction ? '[name]_[hash:base64:8]' : '[path][name]__[local]',
                                localIdentContext: path.join(__dirname, 'resources/scripts/components'),
                            },
                            sourceMap: !isProduction,
                            importLoaders: 1,
                        },
                    },
                    {
                        loader: 'postcss-loader',
                        options: { sourceMap: !isProduction },
                    },
                ],
            },
            {
                test: /\.(png|jp(e?)g|gif|avif)$/,
                loader: 'file-loader',
                options: {
                    name: 'images/[name].[hash:8].[ext]',
                },
            },
            {
                test: /\.svg$/,
                loader: 'svg-url-loader',
            },
            {
                test: /\.js$/,
                enforce: 'pre',
                loader: 'source-map-loader',
            }
        ],
    },
    stats: {
        warningsFilter: [/Failed to parse source map/],
    },
    resolve: {
        extensions: ['.ts', '.tsx', '.js', '.json', '.mjs'],
        alias: {
            '@': path.join(__dirname, '/resources/scripts'),
            '@resources': path.join(__dirname, '/resources/scripts'),
            '@definitions': path.join(__dirname, '/resources/scripts/api/definitions'),
            '@feature': path.join(__dirname, '/resources/scripts/components/server/features'),
            '@rolexdev': path.join(__dirname, '/rolexdev'),
            'motion/react': path.resolve(__dirname, 'node_modules/motion/dist/cjs/react.js'),
            'tailwind-merge': path.resolve(__dirname, 'node_modules/tailwind-merge/dist/bundle-mjs.mjs'),
            'state-local': path.resolve(__dirname, 'node_modules/state-local/lib/cjs/state-local.js'),
            'state-local/lib/es/state-local.js': path.resolve(
                __dirname,
                'node_modules/state-local/lib/cjs/state-local.js'
            ),
        },
        symlinks: false,
        mainFields: ['main', 'module', 'browser'],
    },
    externals: {
        moment: 'moment',
    },
    plugins: [
        new webpack.EnvironmentPlugin({
            NODE_ENV: 'development',
            DEBUG: process.env.NODE_ENV !== 'production',
            WEBPACK_BUILD_HASH: Date.now().toString(16),
        }),
        new AssetsManifestPlugin({ writeToDisk: true, publicPath: true, integrity: true, integrityHashes: ['sha384'] }),
        (!disableTsChecker ? new ForkTsCheckerWebpackPlugin({
            async: false,
            typescript: {
                memoryLimit: 4096,
                mode: 'write-references',
                diagnosticOptions: {
                    semantic: true,
                    syntactic: true,
                },
            },
            eslint: isProduction
                ? undefined
                : {
                    files: `${path.join(__dirname, '/resources/scripts')}/**/*.{ts,tsx}`,
                },
        }) : null),
        process.env.ANALYZE_BUNDLE ? new BundleAnalyzerPlugin({
            analyzerHost: '0.0.0.0',
            analyzerPort: 8081,
        }) : null
        ,
        new MonacoWebpackPlugin({
            languages: [
                'javascript',
                'typescript',
                'json',
                'css',
                'html',
                'python',
                'php',
                'java',
                'csharp',
                'cpp',
                'ruby',
                'go',
                'rust',
                'sql',
                'shell',
                'yaml',
                'xml',
                'markdown',
            ],
            features: [
                '!accessibilityHelp',
                '!bracketMatching',
                '!caretOperations',
                '!clipboard',
                '!comment',
                '!contextmenu',
                '!coreCommands',
                '!cursorUndo',
                '!dnd',
                '!find',
                '!folding',
                '!format',
                '!hover',
                '!inPlaceReplace',
                '!inspectTokens',
                '!iPadShowKeyboard',
                '!linesOperations',
                '!linkedEditing',
                '!links',
                '!multicursor',
                '!parameterHints',
                '!quickCommand',
                '!quickHelp',
                '!quickOutline',
                '!referenceSearch',
                '!rename',
                '!smartSelect',
                '!suggest',
                '!toggleHighContrast',
                '!walkThrough',
                '!viewportSemanticTokens',
            ],
        }),
    ].filter(p => p),
    optimization: {
        usedExports: true,
        sideEffects: false,
        runtimeChunk: 'single',
        removeEmptyChunks: true,
        minimize: isProduction,
        minimizer: [
            new TerserPlugin({
                cache: isProduction,
                parallel: false,
                extractComments: false,
                terserOptions: {
                    mangle: true,
                    output: {
                        comments: false,
                    },
                },
            }),
        ],
    },
    watchOptions: {
        poll: 1000,
        ignored: /node_modules/,
    },
    devServer: {
        compress: true,
        contentBase: path.join(__dirname, '/public'),
        publicPath: process.env.WEBPACK_PUBLIC_PATH || '/assets/',
        allowedHosts: [
            '.pterodactyl.test',
        ],
        headers: {
            'Access-Control-Allow-Origin': '*',
        },
    },
};