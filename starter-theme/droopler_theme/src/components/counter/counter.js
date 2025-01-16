(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.d_p_counters = {
    attach: function (context, settings) {
      var cnt = 0;
      var options = {
        useEasing: true,
        useGrouping: true,
        separator: ' ',
        decimal: ',',
      };

      // Find all counters.
      $('[data-count]', context).each(function () {
        // Trigger if in viewport.
        inViewport(this, function (el) {
          var id = 'upcnt' + cnt++;
          $(el).attr('id', id);

          // Count up.
          var numAnim = new CountUp(id, 0, $(el).data('count'), 0, 2, options);
          numAnim.start();
        });
      });
    }
  };
})(jQuery, Drupal);
