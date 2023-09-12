(function ($, Drupal) {

  'use strict';

  // Colorpicker + reset button.
  Drupal.behaviors.dpColorpicker = {
    attach: function (context, settings) {
      $(context).ready(function () {
        $('.form-type-d-color', context).each(function () {
          var $element = $(this);

          var $color = $element.children('.form-d-color').first();
          $color.change(function () {
            if ($(this).val() === '#ffffff') {
              $reset.hide();
            }
            else if ($reset.is(":hidden")) {
              $reset.show();
            }
          });

          var $reset = $element.children('#reset-button').first();
          $reset.click(function (e) {
            e.preventDefault();
            $color.val('#ffffff');
            $(this).hide();
          });
        });
      });
    }
  };

})(jQuery, Drupal);
