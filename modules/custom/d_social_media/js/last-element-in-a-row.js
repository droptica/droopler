/**
 * Style all far right elements in horizontal list.
 */
(function ($, Drupal) {
  Drupal.behaviors.dSocialMedia = {
    attach: function (context) {
      setBorders();
      var timeout;

      $(window).once('d_social_media_resize').resize(function () {
        clearTimeout(timeout);
        timeout = setTimeout(setBorders, 100);
      });

      /**
       * Add last-element class for particular social media icons in horizontal list.
       */
      function setBorders() {
        var $liElements = $('.social-media-wrapper ul li', context);
        var lastItemOffset = -1;

        $liElements.removeClass('last-element');

        $liElements.each(function (index, item) {
          if (lastItemOffset !== $(item).offset().top) {
            $($liElements[index - 1]).addClass('last-element');
          }
          lastItemOffset = $(item).offset().top;
        }).promise().done(function () {
          $liElements.last().addClass('last-element');
        });
      }
    }
  };
})(jQuery, Drupal);
