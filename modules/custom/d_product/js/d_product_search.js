(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.search_page_actions = {
    attach: function (context, settings) {

      // Auto submit on sort by change.
      $("form#views-exposed-form-products-list-products-list select")
        .change(function () {
          $("form#views-exposed-form-products-list-products-list").submit();
        });

    }
  };

  Drupal.behaviors.mobile_filters = {
    attach: function (context, settings) {
      $(".region-facets-left").after("<div class='close-area'></div>");
      var $button = $('.top-product-info .block-mobile-filters button.mobile-filter');
      var $buttonClose = $('.region-facets-left button.mobile-filter-close, .close-area');

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

        var $activeFacetCounter = $('.facet-item .is-active').length;
        if ($activeFacetCounter > 0) {
          // $(this).append($('<span></span>').addClass('mobile-filters-counter').text($activeFacetCounter));
          $(this).addClass('mobile-filters-active');
        }
      });
    }
  };

})(jQuery, Drupal);
