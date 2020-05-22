/**
 * @file
 * Products search.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Autosubmit on sort button.
   *
   * @type {{attach: Drupal.behaviors.search_page_actions.attach}}
   */
  Drupal.behaviors.search_page_actions = {
    attach: function (context, settings) {

      // Auto submit on sort by change.
      $("form#views-exposed-form-droopler-commerce-products-list-page-1 select", context).once('d_commerce_products_search_sort').change(function () {
        $(this).closest("form").submit();
      });

    }
  };

  /**
   * Mobile view, menu buttons.
   *
   * @type {{attach: Drupal.behaviors.mobile_filters.attach}}
   */
  Drupal.behaviors.mobile_filters = {
    attach: function (context, settings) {
      $(".region-facets-left", context).after("<div class='commerce-close-area'></div>");
      var $button = $(".top-commerce-products-info .block-commerce-mobile-filters button.mobile-filter", context);
      var $buttonClose = $(".commerce-products-filters .block-commerce-mobile-filters-submit button.mobile-filter-close, .commerce-close-area", context);

      $button.once('d_commerce_products_search_open').click(function () {
        $(".region-facets-left", context).css("left", "0");
        $("body").addClass("commerce-navigation-bar-visible");
      });

      $buttonClose.once('d_commerce_products_search_close').click(function () {
        $(".region-facets-left", context).css("left", "-100%");
        $("body").removeClass("commerce-navigation-bar-visible");
      });
    }
  };

  /**
   * Processing mobile filter, add class active.
   *
   * @type {{attach: Drupal.behaviors.mobie_filters_active.attach}}
   */
  Drupal.behaviors.mobie_filters_active = {
    attach: function (context, settings) {
      $("button.mobile-filter:not(.processed)", context).each(function () {
        $(this).addClass("processed");
        var $activeFacetCounter = $(".facet-item .is-active", context).length;
        if ($activeFacetCounter > 0) {
          $(this).addClass("mobile-filters-active");
        }
      });
    }
  };

})(jQuery, Drupal);
