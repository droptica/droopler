(function ($) {
  'use strict';

  Drupal.behaviors.d_product_slider = {
    attach: function (context, settings) {

      var nav = '.slider-nav .field-content';
      var main = '.slider-main .slider-container';

      var mainSettings = {
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: true,
        fade: true,
        asNavFor: nav
      };

      var navSettings = {
        arrows: false,
        slidesToShow: 4,
        slidesToScroll: 1,
        dots: false,
        centerMode: true,
        focusOnSelect: true,
        asNavFor: main,
        responsive: [
          {
            breakpoint: 768,
            settings: {
              slidesToShow: 3,
              slidesToScroll: 1
            }
          }
        ]
      };

      function createSlick($container, $settings) {
        $($container).not('.slick-initialized').slick($settings);
      }

      $(nav).on('init', checkNavigationChildrenVisibility);
      $(nav).on('breakpoint', checkNavigationChildrenVisibility);

      createSlick(main, mainSettings);
      createSlick(nav, navSettings);
    }
  };

  function checkNavigationChildrenVisibility() {
    var targetTag = $('.slider-nav .slick-track');
    if (!targetTag) {
      return;
    }
    var childElements = targetTag.find('img').length;
    if (childElements <= 4) {
      targetTag.removeClass('slick-track').addClass('slick-track-const');
    }
  }
})(jQuery);
