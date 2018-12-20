/**
 * @file
 * Transforms links into checkboxes.
 */

(function ($) {

  'use strict';

  Drupal.facets = Drupal.facets || {};
  Drupal.behaviors.facetsCheckboxWidget = {
    attach: function (context, settings) {
      Drupal.facets.makeCheckboxes();
    }
  };

  /**
   * Turns all facet links into checkboxes.
   */
  Drupal.facets.makeCheckboxes = function () {
    // Find all checkbox facet links and give them a checkbox.
    var $links = $('.js-facets-checkbox-links .facet-item a');
    $links.once('facets-checkbox-transform').each(Drupal.facets.makeCheckbox);
    // Set indeterminate value on parents having an active trail.
    $('.facet-item--expanded.facet-item--active-trail > input').prop('indeterminate', true);
  };

  /**
   * Replace a link with a checked checkbox.
   */
  Drupal.facets.makeCheckbox = function () {
    var $link = $(this);
    var active = $link.hasClass('is-active');
    var description = $link.html();
    var href = $link.attr('href');
    var id = $link.data('drupal-facet-item-id');

    var checkbox = $('<input type="checkbox" class="facets-checkbox">')
      .attr('id', id)
      .data($link.data())
      .data('facetsredir', href);
    var label = $('<label for="' + id + '">' + description + '</label>');

    checkbox.on('change.facets', function (e) {
      Drupal.facets.disableFacet($link.parents('.js-facets-checkbox-links'));
      $(this).siblings('a')[0].click();
    });

    if (active) {
      checkbox.attr('checked', true);
      label.find('.js-facet-deactivate').remove();
    }

    $link.before(checkbox).before(label).hide();

  };

  /**
   * Disable all facet checkboxes in the facet and apply a 'disabled' class.
   *
   * @param {object} $facet
   *   jQuery object of the facet.
   */
  Drupal.facets.disableFacet = function ($facet) {
    $facet.addClass('facets-disabled');
    $('input.facets-checkbox', $facet).click(Drupal.facets.preventDefault);
    $('input.facets-checkbox', $facet).attr('disabled', true);
  };

  /**
   * Event listener for easy prevention of event propagation.
   *
   * @param {object} e
   *   Event.
   */
  Drupal.facets.preventDefault = function (e) {
    e.preventDefault();
  };

})(jQuery);
