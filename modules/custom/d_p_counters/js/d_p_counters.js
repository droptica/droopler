(function ($) {
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
      $('.paragraph--type--d-p-group-of-counters .field--name-field-d-number', context).each(function(){
        // Trigger if in viewport.
        inViewport(this, function(el) {
          var id = 'upcnt' + cnt++;
          $(el).attr('id', id);

          // Count up.
          var numAnim = new CountUp(id, 0, $(el).attr('data-count'), 0, 2, options);
          numAnim.start();
        });
      });
    }
  };
})(jQuery);
