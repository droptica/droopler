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
      $('form#views-exposed-form-products-list-products-list select', context).change(function () {
        $(this).closest('form').submit();
      });
    },
  };

  /**
   * Mobile view, menu buttons.
   *
   * @type {{attach: Drupal.behaviors.mobile_filters.attach}}
   */
  Drupal.behaviors.mobile_filters = {
    attach: function (context, settings) {
      const $button = $('.block-mobile-filters button.mobile-filter', context);
      const $buttonClose = $(
        '.page-views__content-column--aside button.mobile-filter-close, .page-views__overlay',
        context,
      );

      $button.click(function () {
        $('body').addClass(['navigation-bar-visible', 'overflow-hidden']);
      });

      $buttonClose.click(function () {
        $('body').removeClass(['navigation-bar-visible', 'overflow-hidden']);
      });

      $(window).resize(function () {
        if (window.innerWidth >= 992) {
          $buttonClose.click();
        }
      });
    },
  };

  /**
   * Processing mobile filter, add class active.
   *
   * @type {{attach: Drupal.behaviors.mobie_filters_active.attach}}
   */
  Drupal.behaviors.mobie_filters_active = {
    attach: function (context, settings) {
      $('button.mobile-filter:not(.processed)', context).each(function () {
        $(this).addClass('processed');
        var $activeFacetCounter = $('.facet-item .is-active', context).length;
        if ($activeFacetCounter > 0) {
          $(this).addClass('mobile-filters-active');
        }
      });
    },
  };
})(jQuery, Drupal);
