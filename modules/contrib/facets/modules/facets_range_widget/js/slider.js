/**
 * @file
 * Provides the slider functionality.
 */

(function ($) {

  'use strict';

  Drupal.facets = Drupal.facets || {};

  Drupal.behaviors.facet_slider = {
    attach: function (context, settings) {
      if (settings.facets !== 'undefined' && settings.facets.sliders !== 'undefined') {
        $.each(settings.facets.sliders, function (facet, settings) {
          Drupal.facets.addSlider(facet, settings);
        });
      }
    }
  };

  Drupal.facets.addSlider = function (facet, settings) {
    var defaults = {
      stop: function (event, ui) {
        if (settings.range) {
          window.location.href = settings.url.replace('__range_slider_min__', ui.values[0]).replace('__range_slider_max__', ui.values[1]);
        }
        else {
          window.location.href = settings.urls['f_' + ui.value];
        }
      }
    };

    $.extend(defaults, settings);

    $('[id^="' + facet +'"][id$="' + facet +'"]').slider(defaults)
    .slider('pips', {
      prefix: settings.prefix,
      suffix: settings.suffix
    })
    .slider('float', {
      prefix: settings.prefix,
      suffix: settings.suffix,
      labels: settings.labels
    });
  };

})(jQuery);
