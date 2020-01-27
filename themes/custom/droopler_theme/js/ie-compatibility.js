(function ($, Drupal) {
  Drupal.behaviors.ie_compatibility = {
    attach: function (context, settings) {
      $(document).ready(function () {
        objectFitImages(null, {watchMQ: true});
      });
    }
  };
})(jQuery, Drupal);
