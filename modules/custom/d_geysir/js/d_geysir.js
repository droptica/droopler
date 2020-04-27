/**
 * @file
 *
 * Custom changes for geysir overlay.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Command to reattach behaviors.
   *
   * @param {Drupal.Ajax} ajax
   *   The Drupal Ajax object.
   * @param {object} response
   *   Object holding the server response.
   * @param {number} [status]
   *   The HTTP status code.
   */
  Drupal.AjaxCommands.prototype.d_geysir_reattach_behaviors = function(ajax, response, status) {
    Drupal.attachBehaviors($('[data-geysir-field-paragraph-field-wrapper]').get(0));
  };

  /**
   * Binds "use-ajax" links.
   *
   * After closing Drupal modal, none of the behaviors
   * nor any of the Drupal ajax commands is executed.
   * Because of this reason we have to register newly
   * added links containing "use-ajax" css class
   * by passing the geysir paragraph wrapper element
   * to bindAjaxLinks method so it could collect it properly.
   *
   * @type {{attach: Drupal.behaviors.d_geysir_bind_ajax_links}}
   */
  Drupal.behaviors.d_geysir_bind_ajax_links = {
    attach: function (context, settings) {
      $(window).on('dialog:afterclose', function (dialog, $element) {
        Drupal.ajax.bindAjaxLinks('[data-geysir-field-paragraph-field-wrapper]');
      });
    }
  }

})(jQuery, Drupal);
