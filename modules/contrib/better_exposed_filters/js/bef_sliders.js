/**
 * @file bef_sliders.js
 *
 * Adds jQuery Slider functionality to an exposed filter.
 */
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.better_exposed_filters_slider = {
    attach: function(context, settings) {
      if (drupalSettings.better_exposed_filters.slider) {
        $.each(drupalSettings.better_exposed_filters.slider_options, function (i, sliderOptions) {
          var id = "input#edit-" + sliderOptions.id;

          // Collect all possible input fields for this filter.
          var $inputs = $(id + ", " + id + "-max, " + id + "-min", context).once('slider-filter');

          if ($inputs.length == 1) {
            // This is a single-value filter.
            var $input = $($inputs[0]);

            // Get the default value. We use slider min if there is no default.
            var default_value = parseFloat(($input.val() == '') ? sliderOptions.min : $input.val(), 10);

            // Set the element value in case we are using the slider min.
            $input.val(default_value);

            // Build the HTML and settings for the slider.
            var slider = $('<div class="bef-slider"></div>').slider({
              min: parseFloat(sliderOptions.min, 10),
              max: parseFloat(sliderOptions.max, 10),
              step: parseFloat(sliderOptions.step, 10),
              animate: sliderOptions.animate ? sliderOptions.animate : false,
              orientation: sliderOptions.orientation,
              value: default_value,
              slide: function (event, ui) {
                $input.val(ui.value);
              },
              // This fires when the value is set programmatically or the stop
              // event fires. This takes care of the case that a user enters a
              // value into the text field that is not a valid step of the
              // slider. In that case the slider will go to the nearest step and
              // this change event will update the text area.
              change: function (event, ui) {
                $input.val(ui.value);
              },
              // Attach stop listeners.
              stop: function (event, ui) {
                // Click the auto submit button.
                $(this).parents('form').find('.ctools-auto-submit-click').click();
              }
            })

            $input.after(slider);

            // Update the slider when the field is updated.
            $input.blur(function () {
              befUpdateSlider($(this), null, sliderOptions);
            });
          }
          else if ($inputs.length == 2) {
            // This is an in-between or not-in-between filter. Use a range
            // filter and tie the min and max into the two input elements.
            var $min = $($inputs[0]),
                $max = $($inputs[1]),
                default_min,
                default_max;

            // Get the default values. We use slider min & max if there are
            // no defaults.
            default_min = parseFloat(($min.val() == '') ? sliderOptions.min : $min.val(), 10);
            default_max = parseFloat(($max.val() == '') ? sliderOptions.max : $max.val(), 10);

            // Set the element value in case we are using the slider min & max.
            $min.val(default_min);
            $max.val(default_max);

            var slider = $('<div class="bef-slider"></div>').slider({
              range: true,
              min: parseFloat(sliderOptions.min, 10),
              max: parseFloat(sliderOptions.max, 10),
              step: parseFloat(sliderOptions.step, 10),
              animate: sliderOptions.animate ? sliderOptions.animate : false,
              orientation: sliderOptions.orientation,
              values: [default_min, default_max],
              // Update the textfields as the sliders are moved
              slide: function (event, ui) {
                $min.val(ui.values[0]);
                $max.val(ui.values[1]);
              },
              // This fires when the value is set programmatically or the
              // stop event fires. This takes care of the case that a user
              // enters a value into the text field that is not a valid step
              // of the slider. In that case the slider will go to the
              // nearest step and this change event will update the text
              // area.
              change: function (event, ui) {
                $min.val(ui.values[0]);
                $max.val(ui.values[1]);
              },
              // Attach stop listeners.
              stop: function(event, ui) {
                // Click the auto submit button.
                $(this).parents('form').find('.ctools-auto-submit-click').click();
              }
            });

            $min.after(slider);

            // Update the slider when the fields are updated.
            $min.blur(function() {
              befUpdateSlider($(this), 0, sliderOptions);
            });
            $max.blur(function() {
              befUpdateSlider($(this), 1, sliderOptions);
            });
          }
        })
      }
    }
  }

  /**
   * Update a slider when a related input element is changed.
   *
   * We don't need to check whether the new value is valid based on slider min,
   * max, and step because the slider will do that automatically and then we
   * update the textfield on the slider's change event.
   *
   * We still have to make sure that the min & max values of a range slider
   * don't pass each other though, however once this jQuery UI bug is fixed we
   * won't have to.
   *
   * @see: http://bugs.jqueryui.com/ticket/3762
   *
   * @param $el
   *   A jQuery object of the updated element.
   * @param valIndex
   *   The index of the value for a range slider or null for a non-range slider.
   * @param sliderOptions
   *   The options for the current slider.
   */
  function befUpdateSlider($el, valIndex, sliderOptions) {
    var val = parseFloat($el.val(), 10),
        currentMin = $el.parents('div.views-widget').next('.bef-slider').slider('values', 0),
        currentMax = $el.parents('div.views-widget').next('.bef-slider').slider('values', 1);
    // If we have a range slider.
    if (valIndex != null) {
      // Make sure the min is not more than the current max value.
      if (valIndex == 0 && val > currentMax) {
        val = currentMax;
      }
      // Make sure the max is not more than the current max value.
      if (valIndex == 1 && val < currentMin) {
        val = currentMin;
      }
      // If the number is invalid, go back to the last value.
      if (isNaN(val)) {
        val = $el.parents('div.views-widget').next('.bef-slider').slider('values', valIndex);
      }
    }
    else {
      // If the number is invalid, go back to the last value.
      if (isNaN(val)) {
        val = $el.parents('div.views-widget').next('.bef-slider').slider('value');
      }
    }
    // Make sure we are a number again.
    val = parseFloat(val, 10);
    // Set the slider to the new value.
    // The slider's change event will then update the textfield again so that
    // they both have the same value.
    if (valIndex != null) {
      $el.parents('div.views-widget').next('.bef-slider').slider('values', valIndex, val);
    }
    else {
      $el.parents('div.views-widget').next('.bef-slider').slider('value', val);
    }
  }

}) (jQuery, Drupal, drupalSettings);
