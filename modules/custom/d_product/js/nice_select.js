(function ($) {

  Drupal.behaviors.sm_nice_select = {
    attach: function (context, settings) {

      $('.select-nice:not(.processed)').each(function () {
        $(this).addClass('processed');

        $(this).select2({
          minimumResultsForSearch: -1
        })
      });
    }
  };
})(jQuery);
