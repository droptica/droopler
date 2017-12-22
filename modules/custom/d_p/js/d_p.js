(function ($) {
  'use strict';

  Drupal.behaviors.d_p_banner = {
    attach: function (context, settings) {

      $(document).ready(function () {
        d_p_resize_image(true);
        $(window).resize(function () {
          d_p_resize_image(false);
        });
      });

      /**
       * Resize image and vertical align paragraph.
       */
      function d_p_resize_image(fade_in) {
        fade_in = (fade_in == true) ? 500 : 0;
        var $background_image = $('.background-image');
        var $paragraph_settings;
        var url = [];
        var i;
        $background_image.each(function () {
          if (typeof drupalSettings.image_background[$(this).data("id")] !== 'undefined') {
            $paragraph_settings = drupalSettings.image_background[$(this).data("id")];
            var container_half_outer = $('.background-image + .container-half').outerHeight();
            if (typeof container_half_outer !== 'undefined') {
              $(this).closest('.d-image').css('height', container_half_outer + 50 + 'px');
            }
            url = [];
            for (i = 0; i < $paragraph_settings.length; i++) {
              url[i] = {
                "width": $paragraph_settings[i].width,
                "url": $paragraph_settings[i].url,
                "fade": fade_in,
              };
            }
            $(this).backstretch([url]);
          }
        });
      }
    }
  };
})(jQuery);
