(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.dpColorpicker = {
    attach: function (context, settings) {
      $(context).ready(function() {
        $('.form-type-d-color', context).once().each(function () {
          var $element = $(this);

          if ($element.hasClass('binded')) {
            return;
          }

          $element.addClass('binded');

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
