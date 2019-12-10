(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.dpColorpicker = {
    attach: function (context, settings) {
      // Add colorpicker and reset button, set background-color field to hidden.
      function d_p_colorpicker() {
        var $thisColorField = $(this);

        var $button = $('<button/>')
          .html(Drupal.t('Reset color'))
          .attr('id', 'reset-button')
          .addClass('button js-form-submit form-submit m-1')
          .click(function(e) {
          e.preventDefault();
          $(this).siblings('input[type=hidden]').val('');
          $(this).siblings('input[type=color]').val('#ffffff').css('opacity', 0.3);
          $(this).hide();
        });

        var $colorpicker = $('<input/>')
          .addClass('m-1')
          .attr('type', 'color')
          .on('input', function(e) {
            $(this).siblings('input[type=hidden]').val($(this).val());
            $(this).css('opacity', 1);
            $(this).siblings('button').show();
          });

        var $parentWrapper = $thisColorField.find('fieldset');
        if ($parentWrapper.length == 0) {
          $parentWrapper = $thisColorField.find('.js-form-item');
        }

        if ($parentWrapper.find('button').length === 0) {
          var $input = $parentWrapper.find('input').attr('type', 'hidden');

          $parentWrapper.append($colorpicker).append($button);
          $colorpicker.val($input.val() || '#ffffff').css('opacity', 1);
          $button.show();

          if (!$input.val()) {
            $button.hide();
            $colorpicker.css('opacity', 0.3);
          }
        }
      }

      $(context).ready(function() {
        $('.field--name-field-d-background-color', context).once().each(d_p_colorpicker);
      });
    }
  };

})(jQuery, Drupal);
