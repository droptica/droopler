(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.customCKEditorConfig = {
    attach: function (context, settings) {

      $(context).ready(function() {
        d_p_ckeditor();
      });

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

      function d_p_ckeditor() {
        if (typeof CKEDITOR !== "undefined") {
          for (let instanceName in CKEDITOR.instances) {
            if (CKEDITOR.instances[instanceName].element.$.parentElement.classList.contains('d_p_ckeditor_centered')) {
              if (typeof CKEDITOR.instances[instanceName].document !== 'undefined') {
                d_p_ckeditor_add_js(CKEDITOR.instances[instanceName].document.$);
              } else {
                setTimeout(function () {
                  d_p_ckeditor_add_js(CKEDITOR.instances[instanceName].document.$);
                }, 500);
              }
            }
          }
        } else {
          window.setTimeout(d_p_ckeditor, 500);
        }
      }
    }
  };
})(jQuery, Drupal);
