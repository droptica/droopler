(function ($, Drupal) {
  // Use strict
  'use strict';

  Drupal.behaviors.page_views_products = {
    attach: function (context, settings) {
      const filtersMobileOpen = $('.mobile-filter', context);
      const filtersMobileClose = $('.mobile-filter-close', context);
      const filters = $('.page-views__aside', context);

      filtersMobileOpen.click(function () {
        filters.css('left', 0);
      });
      
      filtersMobileClose.click(function () {
        filters.css('left', '-100%');
      });
    },
  };
})(jQuery, Drupal);
