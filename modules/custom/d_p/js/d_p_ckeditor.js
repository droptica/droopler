(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.customCKEditorConfig = {
    attach: function (context, settings) {

      d_p_ckeditor(true);
      d_p_ckeditor_add_js(true);

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

      var interval = window.setInterval(d_p_ckeditor, 500);

      function d_p_ckeditor(fade_in) {
        fade_in = (fade_in == true) ? 500 : 0;
        if (typeof CKEDITOR !== "undefined") {
          // Check if it is a geysir paragraph editor
          if (document.getElementById('geysir-modal')) {
            setTimeout(function () {
              try {
                // Check if it is text-blocks-paragraph
                if (typeof CKEDITOR.instances[Object.keys(CKEDITOR.instances)[0]].element.$.parentElement.offsetParent.children['geysir-modal-form'] !== 'undefined') {
                  var doc = CKEDITOR.instances[Object.keys(CKEDITOR.instances)[0]].document.$; // get CKE doc!
                  d_p_ckeditor_add_js(doc);
                }
              } catch (err) {
                // Error handling
              }
            }, 1300);
          } else {
            // For editor paragraph check every instance of ckeditor
            for (var instanceName in CKEDITOR.instances) {
              setTimeout(function () {
                var element = CKEDITOR.instances[instanceName].element.$.parentElement.offsetParent.parentElement.classList;

                // check if it is group of text-block from block-text-paragraph
                if (!(element.contains('paragraph-type--d-p-single-text-block'))) {
                  var doc = CKEDITOR.instances[instanceName].document.$; // get CKE doc!
                  d_p_ckeditor_add_js(doc);
                }
              }, 1300);
            }
          }
        }
      }
    }
  };
})(jQuery, Drupal);
