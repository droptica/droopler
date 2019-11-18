(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.dpColorpicker = {
    attach: function (context, settings) {
      // Add colorpicker and reset button, set background-color field to hidden.
      function d_p_colorpicker() {
        if (document.getElementsByClassName('field--name-field-d-background-color').length !== 0) {
          var colors = document.getElementsByClassName('field--name-field-d-background-color');
          var button = document.createElement('button');
          button.innerHTML = Drupal.t('Reset color');
          button.id = 'reset-button';
          button.classList = 'button js-form-submit form-submit btn btn-primary m-1';
          $(button).click(function(e) {
            e.preventDefault();
            $(this).siblings('input[type=hidden]').val('');
            $(this).siblings('input[type=color]').val('#ffffff').css('opacity', 0.3);
            $(this).hide();
          });

          var colorpicker = document.createElement('input');
          colorpicker.classList = 'm-1';
          $(colorpicker).attr('type', 'color');
          $(colorpicker).on('input', function(e) {
            $(this).siblings('input[type=hidden]').val($(this).val());
            $(this).css('opacity', 1);
            $(this).siblings('button').show();
          });

          for (var i = 0; i < colors.length; i++) {
            var parent = colors[i].getElementsByTagName('fieldset')[0];
            if (parent === undefined) {
              parent = colors[i].getElementsByClassName('js-form-item')[0];
            }
            if (parent.getElementsByTagName('button').length === 0) {
              var input = parent.getElementsByTagName('input')[0];
              input.setAttribute('type', 'hidden');
              parent.appendChild(colorpicker);
              parent.appendChild(button);
              $(colorpicker).val($(input).val() || '#ffffff').css('opacity', 1);
              $(button).show();
              if (!$(input).val()) {
                $(button).hide();
                $(colorpicker).css('opacity', 0.3);
              }
            }
          }
        }
      }

      $(context).ready(function() {
        d_p_colorpicker();
      });
    }
  };

})(jQuery, Drupal);
