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
      $("form#views-exposed-form-products-list-products-list select", context).change(function () {
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
      $(".region-facets-left", context).after("<div class='close-area'></div>");
      var $button = $(".top-product-info .block-mobile-filters button.mobile-filter", context);
      var $buttonClose = $(".region-facets-left button.mobile-filter-close, .close-area", context);

      $button.click(function () {
        $(".region-facets-left", context).css("left", "0");
        $("body").addClass("navigation-bar-visible");
      });

      $buttonClose.click(function () {
        $(".region-facets-left", context).css("left", "-100%");
        $("body").removeClass("navigation-bar-visible");
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
