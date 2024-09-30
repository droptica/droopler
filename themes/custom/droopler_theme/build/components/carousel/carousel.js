/******/ (function() { // webpackBootstrap
var __webpack_exports__ = {};
/*!*********************************************!*\
  !*** ./src/components/carousel/carousel.js ***!
  \*********************************************/
/**
 * @file
 * The script that activates Slick carousels.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.d_p_carousel = {
    attach: function attach(context) {
      $("[data-slick]", context).each(function (index, element) {
        var $carouselElement = $(element);
        var carouselItems = $carouselElement.children().length;
        var slickData = $carouselElement.data('slick');
        if (carouselItems >= 2) {
          $carouselElement.slick();
          if (slickData.slidesToShow >= carouselItems) {
            $carouselElement.addClass('carousel-fixed');
          }
        }
      });
    }
  };
})(jQuery, Drupal);
/******/ })()
;
//# sourceMappingURL=carousel.js.map