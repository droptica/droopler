/**
 * @file
 * Provides Gulp configurations and tasks for compiling paragraphs CSS files
 * from SASS files.
 */

'use strict';

// Load gulp and needed lower level libs.
var gulp = require('gulp'),
  yaml   = require('js-yaml'),
  fs     = require('fs');

// Load gulp options.
var options = yaml.safeLoad(fs.readFileSync('./gulp-options.yml', 'utf8'));

// Lazy load gulp plugins.
// By default gulp-load-plugins will only load "gulp-*" and "gulp.*" tasks,
// so we need to define additional patterns for other modules we are using.
var plugins = require('gulp-load-plugins')(options.gulpLoadPlugins);

// Load gulp tasks.
require('./gulp-tasks.js')(gulp, plugins, options);
