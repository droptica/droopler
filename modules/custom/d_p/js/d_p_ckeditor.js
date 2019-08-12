(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.customCKEditorConfig = {
    attach: function (context, settings) {

      d_p_ckeditor(true);

      function d_p_ckeditor_geysir() {
        if (typeof CKEDITOR.instances[Object.keys(CKEDITOR.instances)[0]].element.$.parentElement.offsetParent.offsetParent.parentElement.offsetParent.children['geysir-modal-form'] !== 'undefined') {
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

      function d_p_add_reset_color_button() {
        if(document.getElementsByClassName('form-type-color').length !== 0) {
          var colors = document.getElementsByClassName('form-type-color');
          var button = document.createElement("button");
          button.innerHTML = "Reset color";
          button.id = "reset-button";
          button.classList = "button js-form-submit form-submit btn btn-primary";
          button.onclick = d_p_reset_color;
          for (var i = 0; i < colors.length; i++) {
            if(colors[i].getElementsByTagName('button').length === 0){
              colors[i].appendChild(button);
            }
          }
        }
      }

      function d_p_reset_color(event) {
        event.preventDefault();
        event.target.previousElementSibling.value = "#000000";
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
