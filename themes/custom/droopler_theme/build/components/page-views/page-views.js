/******/ (function() { // webpackBootstrap
var __webpack_exports__ = {};
/*!*************************************************!*\
  !*** ./src/components/page-views/page-views.js ***!
  \*************************************************/
(function ($, Drupal) {
  // Use strict
  'use strict';

  Drupal.behaviors.page_views_products = {
    attach: function attach(context, settings) {
      var filtersMobileOpen = $('.mobile-filter', context);
      var filtersMobileClose = $('.mobile-filter-close', context);
      var filters = $('.page-views__aside', context);
      filtersMobileOpen.click(function () {
        filters.css('left', 0);
      });
      filtersMobileClose.click(function () {
        filters.css('left', '-100%');
      });
    }
  };
})(jQuery, Drupal);
/******/ })()
;
//# sourceMappingURL=page-views.js.map