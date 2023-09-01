(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.customCKEditorConfig = {
    attach: function (context, settings) {

      $(context).ready(function () {
        d_p_ckeditor();
      });

      // @todo: Rework this to better solution when it will be possible.
      function d_p_ckeditor() {
        if (typeof Drupal.CKEditor5Instances !== "undefined") {
          $(once('d_p_ckeditor_centered', '.d_p_ckeditor_centered', context)).each(function () {
            let editor_id = $(this).find('textarea').attr('data-ckeditor5-id');
            let editor = Drupal.CKEditor5Instances.get(editor_id);
            let $editor_editable = $(this).find('.ck-editor__editable');
            let $alignment_dropdown = $(this).find('.ck-toolbar-dropdown.ck-alignment-dropdown');

            if (typeof editor === 'undefined' || !$editor_editable.length) {
              return;
            }

            // Set default alignment to center.
            $editor_editable.children().each(function () {
              if (!$(this).hasClass('text-align-left') && !$(this).hasClass('text-align-right') && !$(this).hasClass('.text-align-justify')) {
                $(this).addClass('text-align-center');
              }
            });
            editor.setData($editor_editable.html());

            // Add text-align-left class to selected element.
            $(once('d_p_alignment_dropdown', $alignment_dropdown)).on('click', function() {
              $(once('d_p_alignment_dropdown_item_btn', $alignment_dropdown.find('.ck.ck-toolbar__items button'))).on('click', function() {
                let current_alignment = editor.commands.get('alignment').value;
                if (current_alignment === 'left') {
                  editor.editing.view.change(writer => {
                    if (typeof editor?.editing?.view?.document?.selection?.focus?.parent?.parent !== 'undefined') {
                      writer.addClass('text-align-left', editor.editing.view.document.selection.focus.parent.parent);
                    }
                  });
                }
                else {
                  editor.editing.view.change(writer => {
                    if (typeof editor?.editing?.view?.document?.selection?.focus?.parent?.parent !== 'undefined') {
                      writer.removeClass('text-align-left', editor.editing.view.document.selection.focus.parent.parent);
                    }
                  });
                }
                setTimeout(function() {
                  editor.setData($editor_editable.html());
                }, 100);
              });
            });
          });
        } else {
          window.setTimeout(d_p_ckeditor, 500);
        }
      }
    }
  };
})(jQuery, Drupal);
