(function ($, Drupal) {

  /**
   * Replace selectlist to niceselect.
   *
   * @type {{attach: Drupal.behaviors.sm_nice_select.attach}}
   */
  Drupal.behaviors.sm_nice_select = {
    attach: function (context, settings) {

      $('.block-views-exposed-filter-blockdroopler-commerce-products-list-page-1 .form-item-sort-by .form-select:not(.processed)', context)
        .each(function () {
          $(this).addClass('processed');

          $(this).select2({
            minimumResultsForSearch: -1
          })
        });
    }
  };
})(jQuery, Drupal);
