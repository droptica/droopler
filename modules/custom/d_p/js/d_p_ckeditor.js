(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.customCKEditorConfig = {
    attach: function (context, settings) {

      $(context).ready(function () {
        d_p_ckeditor();
      });

      function d_p_ckeditor() {
        if (typeof Drupal.CKEditor5Instances !== 'undefined') {
          $(once('d_p_ckeditor_centered', '.d_p_ckeditor_centered', context)).each(function () {
            const editor_id = $(this).find('textarea').attr('data-ckeditor5-id');
            const editor = Drupal.CKEditor5Instances.get(editor_id);

            if (typeof editor === 'undefined') {
              return;
            }

            const model = editor.model;
            const document = model.document;

            // Set default alignment to center.
            editor.execute('selectAll');
            model.change(writer => {
              const all_blocks = Array.from(document.selection.getSelectedBlocks()).filter(block => editor.commands.get('alignment')._canBeAligned(block));
              for (const block of all_blocks) {
                const current_alignment = block.getAttribute('alignment');
                const element_classes = block.getAttribute('htmlPAttributes')?.classes ?? [];
                const align_classes = ['text-align-left', 'text-align-right', 'text-align-justify'];
                if (typeof current_alignment === 'undefined' && !align_classes.some(i => element_classes.includes(i))) {
                  writer.setAttribute('alignment', 'center', block);
                }
              }
            });

            // Add/remove text-align-left class to/from selected elements.
            editor.commands.get('alignment').on('execute', (evt, args) => {
              const alignment = args[0]?.value;
              const current_focus = document.selection.focus;
              const current_anchor = document.selection.anchor;
              const current_first_range = document.selection.getFirstRange();
              const current_selection_is_backward = document.selection.isBackward;
              const selected_blocks = Array.from(document.selection.getSelectedBlocks()).filter(block => editor.commands.get('alignment')._canBeAligned(block));
              let class_updated = false;

              for (const block of selected_blocks) {
                const block_view = editor.editing.mapper.toViewElement(block);
                if (alignment === 'left') {
                  editor.editing.view.change(writer => {
                    writer.addClass('text-align-left', block_view);
                  });
                  class_updated = true;
                }
                else if (block_view.hasClass('text-align-left')) {
                  editor.editing.view.change(writer => {
                    writer.removeClass('text-align-left', block_view);
                  });
                  class_updated = true;
                }
              }

              if (class_updated) {
                // Save the updated data.
                editor.data.set(editor.editing.view.getDomRoot().getInnerHTML(), {batchType: {isUndoable: true}});

                // Restore the selection.
                model.change(writer => {
                  writer.setSelection(current_first_range, {backward: current_selection_is_backward});
                  writer.setSelectionFocus(current_focus, current_anchor);
                });
              }
            });
          });
        } else {
          window.setTimeout(d_p_ckeditor, 500);
        }
      }
    }
  };
})(jQuery, Drupal);
