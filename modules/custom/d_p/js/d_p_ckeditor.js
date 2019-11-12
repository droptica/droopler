(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.customCKEditorConfig = {
    attach: function (context, settings) {

      $(context).ready(function() {
        d_p_ckeditor(true);
      })

      function d_p_ckeditor_geysir() {
        if (typeof CKEDITOR.instances[Object.keys(CKEDITOR.instances)[0]].element.$.parentElement.offsetParent.offsetParent.children['geysir-modal-form'] !== 'undefined') {
          if (typeof CKEDITOR.instances[Object.keys(CKEDITOR.instances)[0]].document !== 'undefined') {
            d_p_add_reset_color_button();
            var doc = CKEDITOR.instances[Object.keys(CKEDITOR.instances)[0]].document.$;
            d_p_ckeditor_add_js(doc);
          } else {
            setTimeout(d_p_ckeditor_geysir, 500);
          }
        }
      }

      function d_p_ckeditor_notgeysir() {
        for (var instanceName in CKEDITOR.instances) {
          var element = CKEDITOR.instances[instanceName].element.$.parentElement.offsetParent.parentElement.classList;
          // check if it is group of text-block from block-text-paragraph
          if (!(element.contains('paragraph-type--d-p-single-text-block'))) {
            if (typeof CKEDITOR.instances[Object.keys(CKEDITOR.instances)[0]].document !== 'undefined') {
                d_p_add_reset_color_button();
              var doc = CKEDITOR.instances[Object.keys(CKEDITOR.instances)[0]].document.$; //get CKE doc!
              d_p_ckeditor_add_js(doc);
            } else {
              setTimeout(d_p_ckeditor_notgeysir, 500);
            }
          }
        }
      }

      // Add colorpicker and reset button, set background-color field to hidden.
      function d_p_add_reset_color_button() {
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

      function d_p_ckeditor_add_js(doc) {
        var cssId = 'd_p_ckeditor';
        if (!doc.getElementById(cssId)) {
          // add css for cke_editable class in ckeditor
          var head = doc.getElementsByTagName('head')[0];
          var link = doc.createElement('link');
          link.id = cssId;
          link.rel = 'stylesheet';
          link.type = 'text/css';
          link.href = '/profiles/contrib/droopler/modules/custom/d_p/css/d_p_ckeditor.css';
          head.appendChild(link);
        }
      }

      function d_p_ckeditor(fade_in) {
        fade_in = (fade_in == true) ? 500 : 0;
        if (typeof CKEDITOR !== "undefined") {
          // Check if it is a geysir paragraph editor
          if (document.getElementById('geysir-modal')) {
            d_p_ckeditor_geysir();
          } else if (typeof CKEDITOR.instances !== "undefined") {
            // For editor paragraph check every instance of ckeditor
            d_p_ckeditor_notgeysir();
          }
        } else {
          window.setTimeout(d_p_ckeditor, 500);
        }
      }
    }
  };
})(jQuery, Drupal);
