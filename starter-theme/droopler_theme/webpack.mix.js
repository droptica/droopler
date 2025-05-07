const path = require('path');

/*
|--------------------------------------------------------------------------
| Mix Asset Management
|--------------------------------------------------------------------------
|
| Mix provides a clean, fluent API for defining some Webpack build steps
| for your application. See https://github.com/JeffreyWay/laravel-mix.
|
*/
const proxy = require('./config/proxy.js');
const mix = require('laravel-mix');
const glob = require('glob');
require('laravel-mix-stylelint');
require('laravel-mix-copy-watched');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const fs = require('fs-extra');


/*
 |--------------------------------------------------------------------------
 | Configuration
 |--------------------------------------------------------------------------
 */
mix
  .sourceMaps()
  .webpackConfig({
    // Use the jQuery shipped with Drupal to avoid conflicts.
    externals: {
      jquery: 'jQuery',
    },
    devtool: 'source-map',
    resolve: {
      alias: {
        '@library': path.resolve(__dirname, '../../../libraries'),
      },
    },
  })
  .setPublicPath('build')
  .disableNotifications()
  .options({
    processCssUrls: false,
  })
  .webpackConfig({
    plugins: [new CleanWebpackPlugin()],
  });

/*
 |--------------------------------------------------------------------------
 | Browsersync
 |--------------------------------------------------------------------------
 */
mix.browserSync({
  proxy: proxy.proxy,
  files: [
    'build/js/**/*.js',
    'build/css/**/*.css',
    'build/components/**/*.css',
    'build/components/**/*.js',
    'src/**/*.twig',
    'templates/**/*.twig',
  ],
  stream: true,
});

/*
 |--------------------------------------------------------------------------
 | SASS
 |--------------------------------------------------------------------------
 */
mix.sass('src/scss/main.style.scss', 'css');

glob.sync('components/**/src/*.scss').forEach((sourcePath) => {
  // Remove the src/ from the path.
  const destinationPath = sourcePath.replace(/^src\/(components\/.+)\/_?(.+)\.scss$/, '$1/$2.css');
  mix.sass(sourcePath, destinationPath, {
    sassOptions: {
      // Prepend all variable imports to each component file
      data: '@import "src/scss/bootstrap/variables'
    }
  }).after(() => {
    // Copy the compiled CSS to the correct location.
    if (sourcePath.startsWith('components/') && destinationPath.endsWith('.scss')) {
      const newDestinationPath = destinationPath.replace('.scss', '.css').replace(/\/src\//, '/');
      fs.copySync('build/' + destinationPath + '.css', newDestinationPath);
    }
  });
});

/*
 |--------------------------------------------------------------------------
 | JS
 |--------------------------------------------------------------------------
 */
mix.js('src/js/main.script.js', 'js');

glob.sync('components/**/*.js').forEach((sourcePath) => {
  const destinationPath = sourcePath.replace(/^src\/(components\/.+)\/(.+)\.js$/, '$1/$2.js');

  mix.js(sourcePath, destinationPath).after(() => {
    // Copy the compiled JS to the correct location.
    if (sourcePath.startsWith('components/')) {
      const newDestinationPath = destinationPath.replace(/\/src\//, '/');
      fs.copySync('build/' + destinationPath, newDestinationPath);
    }
  });
});
/*
 |--------------------------------------------------------------------------
 | Style Lint
 |--------------------------------------------------------------------------
 */
mix.stylelint({
  configFile: './.stylelintrc.json',
  context: './src',
  failOnError: false,
  files: ['**/*.scss'],
  quiet: false,
  customSyntax: 'postcss-scss',
});

/*
 |--------------------------------------------------------------------------
 * IMAGES / ICONS / VIDEOS / FONTS
 |--------------------------------------------------------------------------
 */
// * Directly copies the images, icons and fonts with no optimizations on the images
mix.copyDirectoryWatched('src/assets/images', 'build/assets/images');
mix.copyDirectoryWatched('src/assets/icons', 'build/assets/icons');
mix.copyDirectoryWatched('src/assets/videos', 'build/assets/videos');
mix.copyDirectoryWatched('src/assets/fonts/**/*', 'build/fonts');
