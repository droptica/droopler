(function ($, Drupal) {
  'use strict';

  // Colorpicker + reset button.
  Drupal.behaviors.d_geysir = {
    attach: function (context, settings) {
        $('.geysir-add-type', context).on('click', function () {
          $(this).find('input[name="op"]').mousedown();
        });
    }
  };

})(jQuery, Drupal);
