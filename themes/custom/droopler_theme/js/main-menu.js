/**
 * @file
 * Main menu operation.
 */
(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.mainMenuMobileOperations = {
    attach: function (context, settings) {
      var $block = $('.we-mega-menu-submenu .block-block-content', context);
      var $titles = $('.field--name-field-d-main-title', $block);

      function showHideBlockContents() {
        window.innerWidth < breakpointDesktop ? $contents.hide() : $contents.show();
        $contents.removeClass(visibleClass);
      }

      if ($titles.length) {
        var breakpointDesktop = 992;
        var visibleClass = '.visible-content';
        var blockContentClass = '.field--name-field-d-long-text';
        var $contents = $titles.next(blockContentClass);

        showHideBlockContents();
        $(window).resize(showHideBlockContents);

        $titles.click(function() {
          if (window.innerWidth < breakpointDesktop) {
            var $nextContent = $(this).next(blockContentClass);
            $contents.not($nextContent).slideUp().removeClass(visibleClass);
            $nextContent.not(visibleClass).slideDown().addClass(visibleClass);
          }
        });
      }
    }
  };

})(jQuery, Drupal);
