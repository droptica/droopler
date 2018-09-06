/**
 * @file
 * Attaches simple_sitemap behaviors to the entity form.
 */
(function($) {

  "use strict";

  Drupal.behaviors.simple_sitemapForm = {
    attach: function(context) {

      // On load: Hide the 'Regenerate sitemap' field to only display it if settings have changed.
      $('.form-item-simple-sitemap-regenerate-now').hide();

      // On load: Show or hide settings dependant on 'enabled' setting.
      if ($('#edit-simple-sitemap-index-content-1').is(':checked')) {
        $('.form-item-simple-sitemap-priority').show();
        $('.form-item-simple-sitemap-changefreq').show();
        $('.form-item-simple-sitemap-include-images').show();
      }
      else {
        $('.form-item-simple-sitemap-priority').hide();
        $('.form-item-simple-sitemap-changefreq').hide();
        $('.form-item-simple-sitemap-include-images').hide();
      }

      // On change: Show or hide settings dependant on 'enabled' setting.
      $("#edit-simple-sitemap-index-content").change(function() {
        if ($('#edit-simple-sitemap-index-content-1').is(':checked')) {
          $('.form-item-simple-sitemap-priority').show();
          $('.form-item-simple-sitemap-changefreq').show();
          $('.form-item-simple-sitemap-include-images').show();
        }
        else {
          $('.form-item-simple-sitemap-priority').hide();
          $('.form-item-simple-sitemap-changefreq').hide();
          $('.form-item-simple-sitemap-include-images').hide();
        }
        // Show 'Regenerate sitemap' field if 'enabled' setting has changed.
        $('.form-item-simple-sitemap-regenerate-now').show();
      });

      // Show 'Regenerate sitemap' field if settings have changed.
      $("#edit-simple-sitemap-priority, #edit-simple-sitemap-changefreq, #edit-simple-sitemap-include-images").change(function() {
        $('.form-item-simple-sitemap-regenerate-now').show();
      });
    }
  };
})(jQuery);
