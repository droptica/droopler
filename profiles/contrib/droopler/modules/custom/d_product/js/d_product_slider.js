/**
 * @file
 * Products slider.
 */
(function ($) {
  'use strict';

  /**
   * Products slick slider.
   *
   * @type {{attach: Drupal.behaviors.d_product_slider.attach}}
   */
  Drupal.behaviors.d_product_slider = {
    attach: function (context, settings) {

      var $nav = $('.slider-nav .field-content', context);
      var $main = $('.slider-main .slider-container', context);

      var mainSettings = {
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: true,
        fade: true,
        asNavFor: $nav
      };

      var navSettings = {
        arrows: false,
        slidesToShow: 4,
        slidesToScroll: 1,
        dots: false,
        centerMode: true,
        focusOnSelect: true,
        asNavFor: $main,
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

      $nav.on('init', checkNavigationChildrenVisibility);
      $nav.on('breakpoint', checkNavigationChildrenVisibility);

      createSlick($main, mainSettings);
      createSlick($nav, navSettings);

      /**
       * Create slick instance.
       *
       * @param $container
       * @param $settings
       */
      function createSlick($container, $settings) {
        $($container).not('.slick-initialized').slick($settings);
      }

      /**
       * Check Navigation Children Visibility.
       */
      function checkNavigationChildrenVisibility() {
        var $targetTag = $(this).closest('.slider-nav').find('.slick-track');
        if (!$targetTag) {
          return;
        }
        var childElements = $targetTag.find('img').length;
        if (childElements <= 4) {
          $targetTag.removeClass('slick-track').addClass('slick-track-const');
        }
      }
    }
  };
})(jQuery);
