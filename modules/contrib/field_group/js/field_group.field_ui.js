(function ($) {

  'use strict';
  Drupal.behaviors.fieldUIFieldsOverview = {
    attach: function (context, settings) {
      $('table#field-overview', context).once('field-field-overview', function () {
        Drupal.fieldUIOverview.attach(this, settings.fieldUIRowsData, Drupal.fieldUIFieldOverview);
      });
    }
  };

  /**
   * Row handlers for the 'Manage fields' screen.
   */
  Drupal.fieldUIFieldOverview = Drupal.fieldUIFieldOverview || {};

  Drupal.fieldUIFieldOverview.group = function (row, data) {
    this.row = row;
    this.name = data.name;
    this.region = data.region;
    this.tableDrag = data.tableDrag;

    // Attach change listener to the 'group format' select.
    this.$formatSelect = $('select.field-group-type', row);
    this.$formatSelect.change(Drupal.fieldUIOverview.onChange);

    return this;
  };

  Drupal.fieldUIFieldOverview.group.prototype = {
    getRegion: function () {
      return 'main';
    },

    regionChange: function (region, recurse) {
      return {};
    },

    regionChangeFields: function (region, element, refreshRows) {

      // Create a new tabledrag rowObject, that will compute the group's child
      // rows for us.
      var tableDrag = element.tableDrag;
      var rowObject = new tableDrag.row(element.row, 'mouse', true);
      // Skip the main row, we handled it above.
      rowObject.group.shift();

      // Let child rows handlers deal with the region change - without recursing
      // on nested group rows, we are handling them all here.
      $.each(rowObject.group, function () {
        var childRow = this;
        var childRowHandler = $(childRow).data('fieldUIRowHandler');
        $.extend(refreshRows, childRowHandler.regionChange(region, false));
      });
    }
  };


  /**
   * Row handlers for the 'Manage display' screen.
   */
  Drupal.fieldUIDisplayOverview = Drupal.fieldUIDisplayOverview || {};

  Drupal.fieldUIDisplayOverview.group = function (row, data) {
    this.row = row;
    this.name = data.name;
    this.region = data.region;
    this.tableDrag = data.tableDrag;

    // Attach change listener to the 'group format' select.
    this.$regionSelect = $(row).find('select.field-region');
    this.$regionSelect.on('change', Drupal.fieldUIOverview.onChange);

    return this;
  };

  Drupal.fieldUIDisplayOverview.group.prototype = {
    getRegion: function getRegion() {
      return this.$regionSelect.val();
    },

    regionChange: function (region, recurse) {

      // Default recurse to true.
      recurse = (typeof recurse === 'undefined') || recurse;

      // When triggered by a row drag, the 'region' select needs to be adjusted to
      // the new region.
      region = region.replace(/-/g, '_');
      this.$regionSelect.val(region);

      var refreshRows = {};
      refreshRows[this.name] = this.$regionSelect.get(0);

      if (recurse) {
        this.regionChangeFields(region, this, refreshRows);
      }

      return refreshRows;
    },

    regionChangeFields: function (region, element, refreshRows) {

      // Create a new tabledrag rowObject, that will compute the group's child
      // rows for us.
      var tableDrag = element.tableDrag;
      var rowObject = new tableDrag.row(element.row, 'mouse', true);
      // Skip the main row, we handled it above.
      rowObject.group.shift();

      // Let child rows handlers deal with the region change - without recursing
      // on nested group rows, we are handling them all here.
      $.each(rowObject.group, function () {
        var childRow = this;
        var childRowHandler = $(childRow).data('fieldUIRowHandler');
        $.extend(refreshRows, childRowHandler.regionChange(region, false));
      });

    }

  };

})(jQuery);
