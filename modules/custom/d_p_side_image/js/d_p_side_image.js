(function ($) {
  'use strict';

  Drupal.behaviors.d_paragraphs_sidebar_image = {
    attach: function (context, settings) {
      $(window).on("load", function () {
        d_paragraphs_resize_sidebar_image()
      });
      $(window).resize(function () {
        d_paragraphs_resize_sidebar_image()
      });

      /**
       * Resize image and vertical align paragraph.
       */
      function d_paragraphs_resize_sidebar_image() {
        if (typeof drupalSettings.d_p_side_image !== 'undefined') {
          var $banner = $('.d-p-side-image-banner').find('.d-p-side-image-banner-wrapper');
          var $paragraph_settings;
          var url = [];
          var i;
          $banner.each(function () {
            if (typeof drupalSettings.d_p_side_image[$(this).find('.d-image').data("id")] !== 'undefined') {
              $paragraph_settings = drupalSettings.d_p_side_image[$(this).find('.d-image').data("id")];
              var a = $(this).find('.container-half').outerHeight();
              $(this).find('.d-image').css('height', a + 50 + 'px');
              url = [];
              for (i = 0; i < $paragraph_settings.length; i++) {
                url[i] = {
                  "width": $paragraph_settings[i].width,
                  "url": $paragraph_settings[i].url,
                  "fade": '100',
                  "preload": 1,
                };
              }
              $(this).find('.d-image').backstretch([url]);
            }
          });
        }
      }
    }
  };
})(jQuery);
