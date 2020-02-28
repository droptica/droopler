/**
 * @file
 * Behaviors for internet explorer compatibility libraries.
 */

(function ($, Drupal) {
  Drupal.behaviors.ie_compatibility = {
    attach: function (context, settings) {
      $(document).ready(function () {
        // Null to keep the objectFitImage in auto mode, watchMQ to make it media query aware.
        objectFitImages(null, {watchMQ: true});
      });
    }
  };
})(jQuery, Drupal);
