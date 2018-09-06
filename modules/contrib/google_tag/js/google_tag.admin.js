/**
 * @file
 * Behaviors and utility functions for administrative pages.
 *
 * @author Jim Berry ("solotandem", http://drupal.org/user/240748)
 */

(function ($) {

  "use strict";

  /**
  * Provides summary information for the vertical tabs.
  */
  Drupal.behaviors.gtmInsertionSettings = {
    attach: function (context) {

      $('details#edit-path', context).drupalSetSummary(function (context) {
        var $radio = $('input[name="path_toggle"]:checked', context);
        if ($radio.val() == 'exclude listed') {
          if (!$('textarea[name="path_list"]', context).val()) {
            return Drupal.t('All paths');
          }
          else {
            return Drupal.t('All paths except listed paths');
          }
        }
        else {
          if (!$('textarea[name="path_list"]', context).val()) {
            return Drupal.t('No paths');
          }
          else {
            return Drupal.t('Only listed paths');
          }
        }
      });

      $('details#edit-role', context).drupalSetSummary(function (context) {
        var vals = [];
        $('input[type="checkbox"]:checked', context).each(function () {
          vals.push($.trim($(this).next('label').text()));
        });
        var $radio = $('input[name="role_toggle"]:checked', context);
        if ($radio.val() == 'exclude listed') {
          if (!vals.length) {
            return Drupal.t('All roles');
          }
          else {
            return Drupal.t('All roles except selected roles');
          }
        }
        else {
          if (!vals.length) {
            return Drupal.t('No roles');
          }
          else {
            return Drupal.t('Only selected roles');
          }
        }
      });

      $('details#edit-status', context).drupalSetSummary(function (context) {
        var $radio = $('input[name="status_toggle"]:checked', context);
        if ($radio.val() == 'exclude listed') {
          if (!$('textarea[name="status_list"]', context).val()) {
            return Drupal.t('All statuses');
          }
          else {
            return Drupal.t('All statuses except listed statuses');
          }
        }
        else {
          if (!$('textarea[name="status_list"]', context).val()) {
            return Drupal.t('No statuses');
          }
          else {
            return Drupal.t('Only listed statuses');
          }
        }
      });
    }
  };

})(jQuery);
