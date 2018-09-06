/**
 * @file
 * Attaches simple_sitemap behaviors to the entity form.
 */
(function($) {

  "use strict";

  Drupal.behaviors.simple_sitemapFieldsetSummaries = {
    attach: function(context) {
      $(context).find('#edit-simple-sitemap').drupalSetSummary(function(context) {
        var vals = [];
        if ($(context).find('#edit-simple-sitemap-index-content-1').is(':checked')) {
          vals.push(Drupal.t('Included in sitemap'));
          vals.push(Drupal.t('Priority') + ': ' + $('#edit-simple-sitemap-priority option:selected', context).text());
          vals.push(Drupal.t('Change frequency') + ': ' + $('#edit-simple-sitemap-changefreq option:selected', context).text());
          vals.push(Drupal.t('Include images') + ': ' + $('#edit-simple-sitemap-include-images option:selected', context).text());
        }
        else {
          vals.push(Drupal.t('Excluded from sitemap'));
        }
        return vals.join('<br />');
      });
    }
  };
})(jQuery);
