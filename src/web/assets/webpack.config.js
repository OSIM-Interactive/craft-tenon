const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const path = require('path');

module.exports = {
    entry: {
        cp: './cp/src/index.js',
        overlay: './overlay/src/index.js',
    },
    output: {
        filename: '[name]/dist/js/index.js',
        path: __dirname,
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: '[name]/dist/css/index.css',
        }),
    ],
    module: {
        rules: [
            {
                test: /\.m?js$/,
                exclude: /node_modules/,
                use: {
                    loader: "babel-loader",
                    options: {
                        presets: ['@babel/preset-env']
                    }
                }
            },
            {
                test: /\.s?css$/,
                use: [
                    {
                        loader: MiniCssExtractPlugin.loader,
                        options: {
                            publicPath: '../',
                        },
                    },
                    'css-loader',
                    'sass-loader',
                ],
            },
        ]
    }
};
