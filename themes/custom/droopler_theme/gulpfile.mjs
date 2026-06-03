/**
 * @file
 * Gulp pipeline for droopler_theme (ESM, Gulp 5, Dart Sass quiet build).
 *
 * Commands:
 * - watch / watchFiles — SCSS, JS vendor copy, uglify (Ctrl+C)
 * - debug — path sanity check
 * - clean — built CSS, js/min, js/vendor artifacts
 * - compile — dev assets + Bootstrap bundles into js/vendor
 * - dist — production CSS + bundles + minified theme JS
 */

'use strict';

import fs from 'node:fs';

import { deleteAsync } from 'del';
import gulp from 'gulp';
import autoprefixer from 'gulp-autoprefixer';
import dartSass from 'gulp-dart-sass';
import rename from 'gulp-rename';
import sourcemaps from 'gulp-sourcemaps';
import uglify from 'gulp-uglify';
import pump from 'pump';

const scss_pattern = '**/*.scss';
const js_pattern = '*.js';

const theme_dir = '.';
const scss_dir = `${theme_dir}/scss`;
const css_dir = `${theme_dir}/css`;
const js_dir = `${theme_dir}/js`;
const jsmin_dir = `${theme_dir}/js/min`;
const vendor_dir = `${theme_dir}/js/vendor`;

const scss_input = `${scss_dir}/${scss_pattern}`;
const js_input = `${js_dir}/${js_pattern}`;

/** @see https://sass-lang.com/documentation/js-api/interfaces/Options/ */
const sassSharedOptions = {
  quietDeps: true,
  silenceDeprecations: [
    'legacy-js-api',
    'import',
    'global-builtin',
    'color-functions',
    'if-function',
    'abs-percent',
  ],
};

const sassOptionsDev = {
  ...sassSharedOptions,
  outputStyle: 'expanded',
};

const sassOptionsProd = {
  ...sassSharedOptions,
  outputStyle: 'compressed',
};

const autoprefixerOptions = {
  overrideBrowserslist: ['last 2 versions', '> 5%', 'Firefox ESR'],
};

const bootstrapBundles = [
  'node_modules/bootstrap/dist/js/bootstrap.bundle.js',
  'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js',
  'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js.map',
];

function sassCompile() {
  return gulp
    .src(scss_input)
    .pipe(sourcemaps.init())
    .pipe(dartSass.sync(sassOptionsDev).on('error', dartSass.logError))
    .pipe(autoprefixer(autoprefixerOptions))
    .pipe(sourcemaps.write('./maps'))
    .pipe(gulp.dest(css_dir))
    .resume();
}

function sassDist() {
  return gulp
    .src(scss_input)
    .pipe(dartSass.sync(sassOptionsProd))
    .pipe(autoprefixer(autoprefixerOptions))
    .pipe(gulp.dest(css_dir));
}

/** Copy Bootstrap 4 bundles into js/vendor for libraries.yml. */
function jsCopyLibs() {
  return gulp.src(bootstrapBundles).pipe(gulp.dest(vendor_dir));
}

function jsCompile(cb) {
  pump(
    [
      gulp.src(js_input),
      sourcemaps.init(),
      uglify(),
      rename({ suffix: '.min' }),
      sourcemaps.write('.'),
      gulp.dest(jsmin_dir),
    ],
    cb,
  );
}

function watchFiles() {
  gulp.watch(scss_input, gulp.series(sassCompile));
  gulp.watch(js_input, gulp.series(jsCompile));
  gulp.watch(bootstrapBundles, gulp.series(jsCopyLibs));
}

function debug(cb) {
  console.log(`[OK] Working directory set: ${theme_dir}`);

  if (!fs.existsSync(theme_dir)) {
    console.log('[ERROR] Working directory does not exist (Docker mount / path).');
    cb();
    return;
  }
  console.log('[OK] Working directory exists.');

  if (fs.existsSync(scss_dir)) {
    console.log('[OK] SCSS directory exists.');
  }
  else {
    console.log('[ERROR] SCSS directory does not exist.');
  }

  if (fs.existsSync(css_dir)) {
    console.log('[OK] CSS directory exists.');
  }
  else {
    console.log('[WARNING] CSS directory missing (create before compile).');
  }

  if (fs.existsSync(js_dir)) {
    console.log('[OK] JS directory exists.');
  }
  else {
    console.log('[ERROR] JS directory does not exist.');
  }

  if (fs.existsSync(jsmin_dir)) {
    console.log('[OK] js/min exists.');
  }
  else {
    console.log('[WARNING] js/min missing (create before compile).');
  }

  if (fs.existsSync(vendor_dir)) {
    console.log('[OK] js/vendor exists.');
  }
  else {
    console.log('[WARNING] js/vendor missing (gulp will create output on jsCopyLibs).');
  }

  cb();
}

function clean() {
  return deleteAsync(
    [`${css_dir}/*`, `${jsmin_dir}/*`, `${vendor_dir}/*`],
    { force: true },
  );
}

const compile = gulp.parallel(sassCompile, jsCopyLibs, jsCompile);
const dist = gulp.parallel(sassDist, jsCopyLibs, jsCompile);

export {
  clean,
  compile,
  debug,
  dist,
  jsCompile,
  jsCopyLibs,
  sassCompile,
  sassDist,
  watchFiles,
  watchFiles as watch,
};

export default watchFiles;

process.on('SIGINT', () => {
  console.log('Caught Ctrl+C...');
  process.exit();
});
process.on('SIGTERM', () => {
  console.log('Caught kill...');
  process.exit();
});
