/**
 * @file
 * Attaches behaviors for the Geysir module.
 */

(function($, Drupal, drupalSettings) {

  "use strict";

  Drupal.behaviors.geysir = {
    attach: function(context, settings) {
      var cut_links = $('.geysir-field-paragraph-links', context).find('.cut');
      cut_links.on('click', function (e) {
        e.preventDefault();

        var $this = $(e.target);

        // Find parent paragraph.
        var parent = $this.closest('[data-geysir-paragraph-id]');
        var parent_id = parent.data('geysir-paragraph-id');
        parent.addClass('geysir-cut-paste-disabled');

        // Find the geysir field wrapper.
        var field_wrapper_id = $this.data('geysir-field-paragraph-field-wrapper');
        var field_wrapper = $('[data-geysir-field-paragraph-field-wrapper="' + field_wrapper_id + '"]', context);
        // Add class to the geysir field wrapper to toggle cut behavior.
        field_wrapper.addClass('geysir-cut-paste');

        // Rewrite all paste links based on the paragraph which is currently cut.
        var paragraphs = $('[data-geysir-paragraph-id]', field_wrapper);
        paragraphs.each(function(index, paragraph) {
          paragraph = $(paragraph);
          var paragraph_id = paragraph.data('geysir-paragraph-id');
          var paste_link_wrappers = $('.paste-after, .paste-before', paragraph);
          var paste_links = paste_link_wrappers.find('a');
          paste_links.each(function(index, paste_link) {
            paste_link = $(paste_link);
            var href = paste_link.attr('href');

            $.each(Drupal.ajax.instances, function (index, event) {
              var element = $(event.element);
              if (element.hasClass('geysir-paste')) {
                if (href === event.element_settings.url) {
                  event.options.url = event.options.url.replace('/' + paragraph_id + '/', '/' + parent_id + '/');
                }
              }
            });
          });
        });

        return false;
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
