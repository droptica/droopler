/**
 * @file
 *
 * Custom changes for geysir overlay.
 */

(function ($) {
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
    if (ajax.$form && ajax.$form.length > 0) {
      Drupal.attachBehaviors(ajax.$form.get(0));
    }
  };
})(jQuery);
