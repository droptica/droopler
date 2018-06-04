// Define gulp tasks.
module.exports = function(gulp, plugins, options) {

  'use strict';

  // Processor for linting is assigned to options so it can be reused later.
  options.processors = [
    // Options are defined in .stylelintrc.yaml file.
    plugins.stylelint(options.stylelintOptions),
    plugins.reporter(options.processorsOptions.reporterOptions)
  ];

  // Post CSS options.
  options.postcssOptions = [
    plugins.autoprefixer(options.autoprefixer)
  ];

  // Defining gulp tasks.

  gulp.task('sass', function() {
    return gulp.src(options.scssSrc + '/*.scss')
      .pipe(plugins.sass({
        outputStyle: 'expanded',
        includePaths: options.sassIncludePaths
      }))
      .pipe(plugins.postcss(options.postcssOptions))
      .pipe(gulp.dest(options.cssDest));
  });

  gulp.task('sass:lint', function () {
    return gulp.src(options.scssSrc + '/*.scss')
      .pipe(plugins.postcss(options.processors, {syntax: plugins.syntax_scss}))
  });

  // Default task to run everything in correct order.
  gulp.task('default', ['sass:lint', 'sass']);
};
