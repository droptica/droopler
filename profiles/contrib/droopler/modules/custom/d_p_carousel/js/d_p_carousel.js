/**
 * @file
 * The script that activates Slick carousels.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.d_p_carousel = {
    attach: function (context, settings) {
      
      $('.field--name-field-d-p-cs-item-reference', context).each(function () {
        var cnt = $(this).find('.d-p-carousel-item').length;

        // If there are no elements - do not activate Slick.
        if (cnt <= 1) {
          return;
        }

        var id = $(this).closest('.paragraph').attr('data-id');

        $(this).slick({
          infinite: true,
          slidesToShow: settings.d_p_carousel[id].columns,
          slidesToScroll: 1,
          swipeToSlide: true,
          touchMove: true,
          autoplay: true,
          autoplaySpeed: 3000,

          responsive: [
            {
              breakpoint: settings.d_p_carousel.xs,
              settings: {
                arrows: true,
                slidesToShow: settings.d_p_carousel[id].columns_xs
              }
            },
            {
              breakpoint: settings.d_p_carousel.sm,
              settings: {
                arrows: true,
                slidesToShow: settings.d_p_carousel[id].columns_sm
              }
            }
          ],
        });
      });
    }
  };
})(jQuery);
