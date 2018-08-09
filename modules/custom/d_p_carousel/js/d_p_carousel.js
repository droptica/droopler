(function ($) {
  'use strict';

  Drupal.behaviors.d_p_carousel = {
    attach: function (context, settings) {
      $('.field--name-field-d-p-cs-item-reference').each(function () {
        var id = $(this).closest('.paragraph').attr('data-id');
        var columns = settings.d_p_carousel[id].columns;

        $(this).slick({
          infinite: true,
          slidesToShow: columns,
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
                slidesToShow: 1
              }
            }
          ]
        });
      });
    }
  };
})(jQuery);
