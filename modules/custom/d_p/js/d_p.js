(function ($, Drupal) {
  'use strict';

  /**
   * Changes for Side by Side paragraph.
   *
   * @type {{attach: Drupal.behaviors.d_p_side_by_side.attach}}
   */
  Drupal.behaviors.d_p_side_by_side = {
    attach: function (context, settings) {
      var container = $('.d-p-side-by-side .items', context);
      container.find('.items-wrapper .list-item-wrapper').each(function (key, value) {
        var $this = $(this);
        if ($this.find('.image-background-container').length) {
          $this.find('.user-image-background').css('background-color', 'unset');
        }
      });
    }
  };

})(jQuery, Drupal);
