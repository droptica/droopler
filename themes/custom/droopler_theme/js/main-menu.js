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

        $titles.once().click(function() {
          if (window.innerWidth < breakpointDesktop) {
            $(this).toggleClass('open').parent().find(blockContentClass).slideToggle();

            return false;
          }
        });
      }
    }
  };

  /**
   * Adds body tag class when mobile sidebar is opened.
   * Required for disabling body scroll and adding overlay.
   *
   * @type {{attach: Drupal.behaviors.mainMenuMobileNavbarListener.attach}}
   */
  Drupal.behaviors.mainMenuMobileNavbarListener = {
    attach: function (context, settings) {
      $ ('#navbar-main button.navbar-toggler').click(function() {
        // Avoids classes toggle while collapsing.
        if((!$('body').hasClass('navbar-open') && !$('.navbar').hasClass('collapsing')) || ($('.navbar').hasClass('show'))) {
          $('body').toggleClass('navbar-open', !$(this).is('[aria-expanded="true"]'));
          $('.navbar').toggleClass('open', !$(this).is('[aria-expanded="true"]'));
        }
      });
    }
  };

  /**
   * Adding toggle behavior for sidebar menu items having submenus.
   *
   * @type {{attach: Drupal.behaviors.mainMenuMobileSubmenuToggle.attach}}
   */
  Drupal.behaviors.mainMenuMobileSubmenuToggle = {
    attach: function (context, settings) {
      var $menuItems = $('.we-mega-menu-li.dropdown-menu', context);
      var $links = $('> a.we-mega-menu-li, > span.we-megamenu-nolink', $menuItems);

      if ($links.length) {
        var blockContentClass = '.we-mega-menu-submenu';

        $links.each(function() {
          var $thisLink = $(this);
          $thisLink.toggleClass('open', $thisLink.parent().is('.active'));

          // The item can be <a> tag or just a <span> if no link available - thena the whole <span> is a toggler.
          var $toggler = $thisLink.is('a') ? $thisLink.find('.d-submenu-toggler') : $thisLink;

          $toggler.once().click(function() {
            var $linkItem = $(this);
            if ($linkItem.is('.d-submenu-toggler')) {
              $linkItem = $linkItem.parent();
            }
            $linkItem.toggleClass('open').next(blockContentClass).find('> .we-mega-menu-submenu-inner').slideToggle();

            return false;
          });
        });
      }
    }
  };


})(jQuery, Drupal);
