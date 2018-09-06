/**
 * Gulpfile.js for Droopler theme.
 *
 * Commands:
 * - watch (default) - watches for changes in CSS and JS, hit Ctrl-C to exit
 * - debug - check if all paths are well set
 * - clean - clean derivative CSS, JS & styleguide
 * - compile - compile DEV version of CSS, JS & styleguide
 * - dist - compile PROD version of CSS, JS & styleguide
 */

var gulp = require('gulp');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var autoprefixer = require('gulp-autoprefixer');
var watch = require('gulp-watch');
var uglify = require('gulp-uglify');
var pump = require('pump');
var fs = require('fs');
var rename = require("gulp-rename");
var del = require('del');
var runSequence = require('run-sequence');

// Patterns
var scss_pattern = '**/*.scss';
var js_pattern = '*.js';

// Theme directory
var theme_dir = '.';
var base_theme_dir = '../../../profiles/contrib/droopler/themes/custom/droopler_theme';

// Subdirectories
var scss_dir = theme_dir + '/scss';
var base_scss_dir = base_theme_dir + '/scss';
var css_dir = theme_dir + '/css';
var js_dir = theme_dir + '/js';
var base_js_dir = base_theme_dir + '/js';
var jsmin_dir = theme_dir + '/js/min';
var base_jsmin_dir = theme_dir + '/js/min/base';

// Inputs
var scss_input = scss_dir + '/' + scss_pattern;
var base_scss_input = base_scss_dir + '/' + scss_pattern;
var js_input = js_dir + '/' + js_pattern;
var base_js_input = base_js_dir + '/' + js_pattern;

// Dev SASS options
var sassOptionsDev = {
  errLogToConsole: true,
  outputStyle: 'expanded'
};

// Prod SASS options
var sassOptionsProd = { 
  outputStyle: 'compressed' 
};

// Autoprefixer options
var autoprefixerOptions = {
  browsers: ['last 2 versions', '> 5%', 'Firefox ESR']
};


// MAIN TASKS
// ----------------------------------------------------

// Default task
gulp.task('default', ['watch' /*, possible other tasks... */]);

// Watch SASS & JS
gulp.task('watch', ['sass:compile', 'js:compile', 'js:compile-base'], function(callback) {
  watch([scss_input, base_scss_input], function(vinyl) {
    console.log('File ' + vinyl.path + ' changed, running tasks...');
    runSequence([ 'sass:compile' ]);
  });
  watch(js_input, function(vinyl) {
    console.log('File ' + vinyl.path + ' changed, running tasks...');
    runSequence('js:compile');
  });
  watch(base_js_input, function(vinyl) {
    console.log('File ' + vinyl.path + ' changed, running tasks...');
    runSequence('js:compile-base');
  });
});

// Debug GULP if anything's wrong
gulp.task('debug', function() {
  console.log('[OK] Working directory set: ' + theme_dir);

  // Check of theme dir is mounted
  if (fs.existsSync(theme_dir)) {
    console.log('[OK] Working directory exists.');
  } else {
    console.log('[ERROR] Working directory does not exist. Maybe it is not mounted by docker? Or there is a misspell?');
  }

  // Check of base theme dir is mounted
  if (fs.existsSync(base_theme_dir)) {
    console.log('[OK] Base theme directory exists.');
  } else {
    console.log('[ERROR] Base theme directory does not exist. Maybe it is not mounted by docker? Or there is a misspell?');
  }

  // Check for SCSS dir
  if (fs.existsSync(scss_dir)) {
    console.log('[OK] SCSS directory exists.');
  } else {
    console.log('[ERROR] SCSS directory does not exist. Create it and get to work!');
  }

  // Check for base SCSS dir
  if (fs.existsSync(base_scss_dir)) {
    console.log('[OK] Base SCSS directory exists.');
  } else {
    console.log('[ERROR] Base SCSS directory does not exist. Create it and get to work!');
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

  // Check for base JS dir
  if (fs.existsSync(base_js_dir)) {
    console.log('[OK] Base JS directory exists.');
  } else {
    console.log('[ERROR] Base JS directory does not exist. Please create it!');
  }
  
  // Check for JS MIN dir
  if (fs.existsSync(jsmin_dir)) {
    console.log('[OK] .min.js directory exists.');
  } else {
    console.log('[WARNING] .min.js directory does not exist. Please create it and don\'t tempt gulp to fail!');
  }
});

// Clean everything
gulp.task('clean', function (cb) {
  return del([
    css_dir + '/*',
    jsmin_dir + '/*'
  ], { force: true });
});

// Clean, then generate the dev assets
gulp.task('compile', ['clean'], function (cb) {
  return runSequence([ 'sass:compile', 'js:compile', 'js:compile-base' ], cb);
});

// Clean, then generate the production assets
gulp.task('dist', ['clean'], function (cb) {
  return runSequence([ 'sass:dist', 'js:compile', 'js:compile-base' ], cb);
});


// HELPER TASKS
// ----------------------------------------------------

// Compile SASS
gulp.task('sass:compile', function () {
  return gulp
    .src(scss_input)
    .pipe(sourcemaps.init())
    .pipe(sass(sassOptionsDev).on('error', sass.logError))
    .pipe(autoprefixer(autoprefixerOptions))
    .pipe(sourcemaps.write('./maps'))
    .pipe(gulp.dest(css_dir))
    // Release the pressure back and trigger flowing mode (drain)
    // See: http://sassdoc.com/gulp/#drain-event
    .resume();
});

// Compile JS
gulp.task('js:compile', function (cb) {
  pump([
    gulp.src(js_input),
    sourcemaps.init(),
    uglify(),
    rename({ suffix: '.min' }),
    sourcemaps.write('.'),
    gulp.dest(jsmin_dir)
  ], cb );
});

// Compile Base JS
gulp.task('js:compile-base', function (cb) {
  pump([
    gulp.src(base_js_input),
    sourcemaps.init(),
    uglify(),
    rename({ suffix: '.min' }),
    sourcemaps.write('.'),
    gulp.dest(base_jsmin_dir)
  ], cb );
});

// Generate the production styles
gulp.task('sass:dist', function () {
  return gulp
  .src(scss_input)
  .pipe(sass(sassOptionsProd))
  .pipe(autoprefixer(autoprefixerOptions))
  .pipe(gulp.dest(css_dir));
});

// For Docker - properly catch signals
// Without this CTRL-C won't stop the app, it will send it to background
process.on('SIGINT', function() { console.log('Caught Ctrl+C...'); process.exit(); }); // Ctrl+C
process.on('SIGTERM', function() { console.log('Caught kill...'); process.exit(); }); // docker stop

