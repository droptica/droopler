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
var styleguide = require('sc5-styleguide');
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
var base_jsmin_dir = base_theme_dir + '/js/min';
var styleguide_dir = theme_dir + '/styleguide';

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

// Watch SASS & KSS & JS
gulp.task('watch', ['sass:compile', 'styleguide:generate', 'js:compile', 'js:compile-base'], function(callback) {
  watch([scss_input, base_scss_input], function(vinyl) {
    console.log('File ' + vinyl.path + ' changed, running tasks...');
    runSequence([ 'sass:compile', 'styleguide:generate' ]);
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

  // Check for base JS MIN dir
  if (fs.existsSync(base_jsmin_dir)) {
    console.log('[OK] Base .min.js directory exists.');
  } else {
    console.log('[WARNING] Base .min.js directory does not exist. Please create it and don\'t tempt gulp to fail!');
  }

  // Check for styleguide dir wirh README.md
  if (fs.existsSync(styleguide_dir + '/README.md')) {
    console.log('[OK] Styleguide\'s README.md found.');
  } else {
    console.log('[ERROR] Styleguide\'s README.md does not exist. Please create it!');
  }
});

// Clean everything
gulp.task('clean', function (cb) {
  return del([
    css_dir + '/*',
    jsmin_dir + '/*',
    styleguide_dir + '/*',
    '!' + styleguide_dir + '/README.md'
  ], { force: true });
});

// Clean, then generate the dev assets
gulp.task('compile', ['clean'], function (cb) {
  return runSequence([ 'sass:compile', 'styleguide:compile', 'js:compile', 'js:compile-base' ], cb);
});

// Clean, then generate the production assets
gulp.task('dist', ['clean'], function (cb) {
  return runSequence([ 'sass:dist', 'styleguide:compile', 'js:compile', 'js:compile-base' ], cb);
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

// Create neccesary files for styleguide
gulp.task('styleguide:init', function() {
  if (!fs.existsSync(styleguide_dir)){
    console.log('Creating Styleguide\'s dir...');
    fs.mkdirSync(styleguide_dir);
  }

  if (!fs.existsSync(styleguide_dir + '/README.md')) {
    console.log('Creating Styleguide\'s README.md...');
    fs.writeFileSync(styleguide_dir + '/README.md', 'Styleguide');
  }
});

// Generate the styleguide
gulp.task('styleguide:generate-with-server', ['styleguide:init'], function() {
  return gulp.src(scss_input)
    .pipe(styleguide.generate({
      title: 'Droopler styleguide',
      server: true,
      port: 3000,
      sideNav: 1,
      commonClass: [ 'html-shadow body-shadow' ],
      rootPath: styleguide_dir,
      overviewPath: styleguide_dir + '/README.md',
      // extraHead: '<img src="http://www.droptica.com/sites/all/themes/dtheme/logo.png">',
    }))
    .pipe(gulp.dest(styleguide_dir));
});

// Generate the styleguide without running the server
gulp.task('styleguide:generate-without-server', ['styleguide:init'], function() {
  return gulp.src(scss_input)
    .pipe(styleguide.generate({
      title: 'Droopler styleguide',
      server: false,
      sideNav: 1,
      commonClass: [ 'html-shadow body-shadow' ],
      rootPath: styleguide_dir,
      overviewPath: styleguide_dir + '/README.md',
    }))
    .pipe(gulp.dest(styleguide_dir));
});

// Apply styles to the styleguide
gulp.task('styleguide:applystyles', function() {
  return gulp.src(scss_input)
    .pipe(sass(sassOptionsDev).on('error', sass.logError))
    .pipe(styleguide.applyStyles())
    .pipe(gulp.dest(styleguide_dir));
});

// Parent task - Compile the styleguide without running the server
gulp.task('styleguide:compile', function() {
  runSequence('styleguide:generate-without-server', 'styleguide:applystyles');
});

// Parent task - Compile the styleguide with running the server
gulp.task('styleguide:generate', function() {
  runSequence('styleguide:generate-with-server', 'styleguide:applystyles');
});

// For Docker - properly catch signals
// Without this CTRL-C won't stop the app, it will send it to background
process.on('SIGINT', function() { console.log('Caught Ctrl+C...'); process.exit(); }); // Ctrl+C
process.on('SIGTERM', function() { console.log('Caught kill...'); process.exit(); }); // docker stop

