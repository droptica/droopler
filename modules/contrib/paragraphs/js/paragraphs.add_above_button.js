/**
 * @file
 * Paragraphs actions JS code for paragraphs actions button.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Handle event when "Add above" button is clicked
   * @param event
   *   clickevent
   */
  var clickHandler = function(event) {
    event.preventDefault();
    var $button = $(this);
    var $add_more_wrapper = $button.closest('table')
      .siblings('.clearfix')
      .find('.paragraphs-add-dialog');
    var delta = $button.closest('tr').index();

    // Set delta before opening of dialog.
    var $delta = $add_more_wrapper.closest('.clearfix')
      .find('.paragraph-type-add-modal-delta');
    $delta.val(delta);
    Drupal.paragraphsAddModal.openDialog($add_more_wrapper, Drupal.t('Add above'));
  };

  /**
   * Process paragraph_AddAboveButton elements.
   */
  Drupal.behaviors.paragraphsAddAboveButton = {
    attach: function (context, settings) {
      var button = '<input class="paragraphs-dropdown-action paragraphs-dropdown-action--add-above button js-form-submit form-submit" type="submit" value="' + Drupal.t('Add above') + '">';
      var $actions = $(context).once().find('.paragraphs-dropdown-actions');
      $actions.each(function() {
        if ($(this).closest('.paragraph-top').hasClass('add-above-on')) {
          $(this).once().prepend(button);
        }
      });
      var $addButtons = $actions.find('.paragraphs-dropdown-action--add-above');
      // "Mousedown" is used since the original actions created by paragraphs
      // use the event "focusout" to hide the actions dropdown.
      $addButtons.on('mousedown', clickHandler);
    }
  };

})(jQuery, Drupal);
