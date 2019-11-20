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

      if ($titles.length) {
        var breakpointDesktop = 992;
        var blockContentClass = '.field--name-field-d-long-text';

        $titles.click(function() {
          if (window.innerWidth < breakpointDesktop) {
            $(this).toggleClass('open').parent().find(blockContentClass).slideToggle();

            return false;
          }
        });
      }
    }
  };

  Drupal.behaviors.mainMenuMobileNavbarListener = {
    attach: function (context, settings) {
      $('#navbar-main button.navbar-toggler').click(function() {
        $('body').toggleClass('navbar-open');
      });
    }
  };

  Drupal.behaviors.mainMenuMobileSubmenuToggle = {
    attach: function (context, settings) {
      var $menuItems = $('.we-mega-menu-li.dropdown-menu', context);
      var $links = $('> a.we-mega-menu-li', $menuItems);

      if ($links.length) {
        var blockContentClass = '.we-mega-menu-submenu';

        $links.each(function() {
          $(this).toggleClass('open', $(this).parent().is('.active'));
        }).find('.d-submenu-toggler').click(function() {
          $(this).parent().toggleClass('open').next(blockContentClass).find('> .we-mega-menu-submenu-inner').slideToggle();

          return false;
        });
      }
    }
  };


})(jQuery, Drupal);
