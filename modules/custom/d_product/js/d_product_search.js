(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.mobile_filters = {
    attach: function (context, settings) {

      var $button = $('.top-product-info .block-mobile-filters button.mobile-filter');
      var $buttonClose = $('.region-facets-left button.mobile-filter-close');

      $button.click(context, function () {
        $('.region-facets-left').css('left', "0");

        $('body').addClass('navigation-bar-visible');

      });

      $buttonClose.click(context, function () {
        $('.region-facets-left').css('left', "-100%");

        $('body').removeClass('navigation-bar-visible');

      });


    }
  };

  Drupal.behaviors.filters_counter = {
    attach: function (context, settings) {
      $('button.mobile-filter:not(.processed)').each(function () {
        $(this).addClass('processed');

        var $activeFacetCounter= $('.facet-item .is-active').length;
        if ($activeFacetCounter > 0) {
          $(this).append($('<span></span>').addClass('mobile-filters-counter').text($activeFacetCounter));
        }
      });
    }
  };

  // Drupal.behaviors.filter_buttons_minify = {
  //   attach: function (context, settings) {
  //     $('.top-product-info:not(.processed)').each(function () {
  //       $(this).addClass('processed');
  //
  //       var filterButtonsTop = $('.top-product-info-sort-wrapper').offset().top;
  //       var bodyTag = $('body');
  //
  //       $(window).on('scroll', function() {
  //         var doc = document.documentElement;
  //         var top = (window.pageYOffset || doc.scrollTop)  - (doc.clientTop || 0);
  //
  //         if (top >= filterButtonsTop) {
  //           bodyTag.addClass('scrolled-buttons');
  //         } else {
  //           bodyTag.removeClass('scrolled-buttons');
  //         }
  //
  //       })
  //     });
  //   }
  // };


})(jQuery, Drupal);
