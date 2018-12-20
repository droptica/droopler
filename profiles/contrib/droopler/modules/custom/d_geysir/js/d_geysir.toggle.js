/**
 * @file
 *
 * Behavior for toggling geysir overlay.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.d_geysir_toggle = {
    attach: function (context, settings) {
      var cookieName = 'd_geysir_toggle';

      // Turn off Geysir on blog.
      $('.node--type-blog-post .geysir-field-paragraph-wrapper', context).addClass('permanently-disabled');

      /**
       * Function for changing button state.
       *
       * @param $button
       *   Button jQuery object.
       * @param value
       *   New value.
       */
      function d_geysir_toggle ($button, value) {
        $button.toggleClass('is-active').attr('aria-pressed', (value === 1) ? 'true' : 'false');
        $('.geysir-field-paragraph-wrapper').toggleClass('disabled');
      }

      $('.toolbar-geysir-toggle', context).once('d-geysir-toggle').each(function () {
        var $button = $(this);
        if (typeof $.cookie(cookieName) !== 'undefined' && $.cookie(cookieName) === '0') {
          d_geysir_toggle($button, $.cookie(cookieName));
        }
        $button.closest('.d-geysir-toolbar-tab').removeClass('hidden');

        // Button click callback.
        $button.click(function () {
          if (typeof $.cookie(cookieName) === 'undefined' || $.cookie(cookieName) === '1') {
            $.cookie(cookieName, '0', {
              expires: 1,
              path: '/',
            });
            d_geysir_toggle($button, 0);
          } else {
            $.cookie(cookieName, '1', {
              expires: -1,
              path: '/',
            });
            d_geysir_toggle($button, 1);
          }
        });
      });
    }
  };
})(jQuery);
