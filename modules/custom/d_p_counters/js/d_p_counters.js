(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.d_p_counters = {
    attach: function (context, settings) {
      var cnt = 0;
      var options = {
        duration: 2,
        useEasing: true,
        useGrouping: true,
        separator: ' ',
        decimal: ',',
      };

      // Find all counters.
      var elements = once('d-p-counters', '.paragraph--type--d-p-group-of-counters .field--name-field-d-number', context);

      $(elements).each(function () {
        // Trigger if in viewport.
        inViewport(this, function (el) {
          el.id = 'upcnt' + cnt++;

          // Count up. The UMD build exposes the class as countUp.CountUp.
          var numAnim = new countUp.CountUp(el, $(el).attr('data-count'), options);
          numAnim.start();
        });
      });
    }
  };
})(jQuery, Drupal, once);
