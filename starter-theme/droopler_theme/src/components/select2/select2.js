(function ($, Drupal) {
  /**
   * Attaches select2 widget.
   *
   * @type {{attach: Drupal.behaviors.sm_nice_select.attach}}
   */
  Drupal.behaviors.select2 = {
    attach: function (context, settings) {
      $('[data-select2] .form-select', context).each(function () {
        if ($(this).find('.form-select').hasClass('select2-hidden-accessible')) {
          return;
        }

        $(this).select2({
          minimumResultsForSearch: -1,
          width: '100%',
        });
      });
    },
  };
})(jQuery, Drupal);
