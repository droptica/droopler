/**
 * Gulpfile.js for Droopler theme.
 *
 * Commands:
 * - watch (default) - watches for changes in CSS and JS, hit Ctrl-C to exit
 * - debug - check if all paths are well set
 * - clean - clean derivative CSS & JS
 * - compile - compile DEV version of CSS & JS
 * - dist - compile PROD version of CSS & JS
 */

(() => {

  'use strict';

  const gulp = require('gulp');
  const sass = require('gulp-sass');
  const sourcemaps = require('gulp-sourcemaps');
  const autoprefixer = require('gulp-autoprefixer');
  const uglify = require('gulp-uglify');
  const pump = require('pump');
  const fs = require('fs');
  const rename = require("gulp-rename");
  const del = require('del');
  const argv = require('yargs').argv;
  const sassVars = require('gulp-sass-vars');

  // Patterns
  const scss_pattern = '**/*.scss';
  const js_pattern = '*.js';

  // Theme directory
  const theme_dir = '.';


  // Subdirectories
  const scss_dir = theme_dir + '/scss';
  const css_dir = theme_dir + '/css';
  const js_dir = theme_dir + '/js';
  const jsmin_dir = theme_dir + '/js/min';
  const vendor_dir = theme_dir + '/js/vendor';


  // Inputs
  const scss_input = scss_dir + '/' + scss_pattern;
  const js_input = js_dir + '/' + js_pattern;


  // Dev SASS options
  const sassOptionsDev = {
    errLogToConsole: true,
    outputStyle: 'expanded'
  };

  // Prod SASS options
  const sassOptionsProd = {
    outputStyle: 'compressed'
  };

  // Autoprefixer options
  const autoprefixerOptions = {
    overrideBrowserslist: ['last 2 versions', '> 5%', 'Firefox ESR']
  };


  // MAIN TASKS
  // ----------------------------------------------------

  // Watch SASS & JS
  function watchFiles() {
    gulp.watch(scss_input, gulp.series(sassCompile));
    gulp.watch(js_input, gulp.series(jsCompile));
  }

  function debug(cb) {
    console.log('[OK] Working directory set: ' + theme_dir);

    // Check of theme dir is mounted
    if (fs.existsSync(theme_dir)) {
      console.log('[OK] Working directory exists.');
    } else {
      console.log('[ERROR] Working directory does not exist. Maybe it is not mounted by docker? Or there is a misspell?');
    }


    // Check for SCSS dir
    if (fs.existsSync(scss_dir)) {
      console.log('[OK] SCSS directory exists.');
    } else {
      console.log('[ERROR] SCSS directory does not exist. Create it and get to work!');
    }


    // Check for CSS dir
    if (fs.existsSync(css_dir)) {
      console.log('[OK] CSS directory exists.');
    } else {
      console.log('[WARNING] CSS directory does not exist. Please create it and don\'t tempt gulp to fail!');
    }

    // Check for JS dir
    if (fs.existsSync(js_dir)) {
      console.log('[OK] JS directory exists.');
    } else {
      console.log('[ERROR] JS directory does not exist. Please create it!');
    }

    // Check for JS MIN dir
    if (fs.existsSync(jsmin_dir)) {
      console.log('[OK] .min.js directory exists.');
    } else {
      console.log('[WARNING] .min.js directory does not exist. Please create it and don\'t tempt gulp to fail!');
    }

    // Check for JS VENDOR dir
    if (fs.existsSync(vendor_dir)) {
      console.log('[OK] js/vendor directory exists.');
    } else {
      console.log('[WARNING] js/vendor directory does not exist. Please create it and don\'t tempt gulp to fail!');
    }

    cb()
  }


  // Clean everything
  function clean(cb) {
    return del([
      css_dir + '/*',
      jsmin_dir + '/*',
      vendor_dir + '/*'
    ], {force: true});
  }

  const compile = gulp.parallel(sassCompile, jsCopyLibs, jsCompile);
  const dist = gulp.parallel(sassDist, jsCopyLibs, jsCompile);


  // HELPER TASKS
  // ----------------------------------------------------

  // Compile SASS
  function sassCompile() {
    let profileUrl = argv.profile_url;
    let variables = {};
    if (typeof profileUrl !== 'undefined') {
      variables = {profile_path: profileUrl};
    }
    return gulp
      .src(scss_input)
      .pipe(sassVars(variables, {verbose: true}))
      .pipe(sourcemaps.init())
      .pipe(sass(sassOptionsDev).on('error', sass.logError))
      .pipe(autoprefixer(autoprefixerOptions))
      .pipe(sourcemaps.write('./maps'))
      .pipe(gulp.dest(css_dir))
      // Release the pressure back and trigger flowing mode (drain)
      // See: http://sassdoc.com/gulp/#drain-event
      .resume();
  }

  // Copy JS libs
  function jsCopyLibs(cb) {
    gulp.src([
      "node_modules/bootstrap/dist/js/bootstrap.bundle.js",      
      "node_modules/bootstrap/dist/js/bootstrap.bundle.min.js",
      "node_modules/bootstrap/dist/js/bootstrap.bundle.min.js.map"
    ]).pipe(gulp.dest(vendor_dir), cb())
  }

  // Compile JS
  function jsCompile(cb) {
    pump([
      gulp.src(js_input),
      sourcemaps.init(),
      uglify(),
      rename({suffix: '.min'}),
      sourcemaps.write('.'),
      gulp.dest(jsmin_dir)
    ], cb);
  }


  // Generate the production styles
  function sassDist() {
    let profileUrl = argv.profile_url;
    let variables = {};
    if (typeof profileUrl !== 'undefined') {
      variables = {profile_path: profileUrl};
    }
    return gulp
      .src(scss_input)
      .pipe(sassVars(variables, {verbose: true}))
      .pipe(sass(sassOptionsProd))
      .pipe(autoprefixer(autoprefixerOptions))
      .pipe(gulp.dest(css_dir));
  }

  exports.compile = compile;
  exports.clean = clean;
  exports.debug = debug;
  exports.jsCopyLibs = jsCopyLibs;
  exports.watch = watchFiles;
  exports.dist = dist;
  exports.sassCompile = sassCompile;
  exports.jsCompile = jsCompile;
  exports.sassDist = sassDist;
  exports.default = exports.watch;


  // For Docker - properly catch signals
  // Without this CTRL-C won't stop the app, it will send it to background
  process.on('SIGINT', function () {
    console.log('Caught Ctrl+C...');
    process.exit();
  }); // Ctrl+C
  process.on('SIGTERM', function () {
    console.log('Caught kill...');
    process.exit();
  }); // docker stop

})();
