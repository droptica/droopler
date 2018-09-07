(function ($) {

  Drupal.behaviors.sm_nice_select = {
    attach: function (context, settings) {

      $('.block-views-exposed-filter-blockproducts-list-products-list #edit-sort-by:not(.processed)').each(function () {
        $(this).addClass('processed');

        $(this).select2({
          minimumResultsForSearch: -1
        })
      });
    }
  };
})(jQuery);
