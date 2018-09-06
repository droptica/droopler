(function ($, Drupal) {

  'use strict';

  /**
  * Set content fields to visible when tabs are created. After an action
  * being performed, stay on the same perspective.
  *
  * @param $parWidget
  *   Paragraphs widget.
  * @param $parTabs
  *   Paragraphs tabs.
  * @param $parContent
  *   Paragraphs content tab.
  * @param $parBehavior
  *   Paragraphs behavior tab.
  * @param $mainRegion
  *   Main paragraph region.
  */
  var setUpTab = function ($parWidget, $parTabs, $parContent, $parBehavior, $mainRegion) {
    var $tabContent = $parTabs.find('#content');
    var $tabBehavior = $parTabs.find('#behavior');
    if ($tabBehavior.hasClass('is-active')) {
      $parWidget.removeClass('content-active').addClass('behavior-active');
      $tabContent.removeClass('is-active');
      $tabBehavior.addClass('is-active');
      $parContent.hide();
      $parBehavior.show();
    }
    else {
      // Activate content tab visually if there is no previously
      // activated tab.
      if (!($mainRegion.hasClass('content-active'))
        && !($mainRegion.hasClass('behavior-active'))) {
        $tabContent.addClass('is-active');
        $mainRegion.addClass('content-active');
      }

      $parContent.show();
      $parBehavior.hide();

      $parTabs.show();
      if ($parBehavior.length === 0) {
        $parTabs.hide();
      }
    }
  };

  /**
  * Switching active class between tabs.
  * @param $parTabs
  *   Paragraphs tabs.
  * @param $clickedTab
  *   Clicked tab.
  * @param $parWidget
  *   Paragraphs widget.
  */
  var switchActiveClass = function ($parTabs, $clickedTab, $parWidget) {
      $parTabs.find('li').removeClass('is-active');
      $clickedTab.parent('li').addClass('is-active');
      $parWidget.removeClass('behavior-active content-active is-active');
      $($parWidget).find($clickedTab.attr('href')).addClass('is-active');

      if ($parWidget.find('#content').hasClass('is-active')) {
        $parWidget.find('.layout-region-node-main').addClass('content-active');
        $parWidget.find('.paragraphs-content').show();
        $parWidget.find('.paragraphs-behavior').hide();
      }

      if ($parWidget.find('#behavior').hasClass('is-active')) {
        $parWidget.find('.layout-region-node-main').addClass('behavior-active');
        $parWidget.find('.paragraphs-content').hide();
        $parWidget.find('.paragraphs-behavior').show();
      }
  };

  /**
  * For body tag, adds tabs for selecting how the content will be displayed.
  *
  * @type {Drupal~behavior}
  */
  Drupal.behaviors.bodyTabs = {
    attach: function (context) {
      var $topLevelParWidgets = $('.paragraphs-tabs-wrapper', context).filter(function() {
        return $(this).parents('.paragraphs-nested').length === 0;
      });

      // Initialization.
      $topLevelParWidgets.once('paragraphs-bodytabs').each(function() {
        var $parWidget = $(this);
        var $parTabs = $parWidget.find('.paragraphs-tabs');

        // Create click event.
        $parTabs.find('a').click(function(e) {
          e.preventDefault();
          switchActiveClass($parTabs, $(this), $parWidget);
        });
      });

      if ($('.paragraphs-tabs-wrapper', context).length > 0) {
        $topLevelParWidgets.each(function() {
          var $parWidget = $(this);
          var $parTabs = $parWidget.find('.paragraphs-tabs');
          var $parContent = $parWidget.find('.paragraphs-content');
          var $parBehavior = $parWidget.find('.paragraphs-behavior');
          var $mainRegion = $parWidget.find('.layout-region-node-main');
          setUpTab($parWidget, $parTabs, $parContent, $parBehavior, $mainRegion);
        });
      }
    }
  };
})(jQuery, Drupal);

