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
      $ ('#navbar-main button.navbar-toggler', context).click(function() {
        // Avoids classes toggle while collapsing.
        if((!$('body').hasClass('navbar-open') && !$('.navbar').hasClass('collapsing')) || ($('.navbar').hasClass('show'))) {
          $('body').toggleClass('navbar-open', !$(this).is('[aria-expanded="true"]'));
          $('.navbar').toggleClass('open', !$(this).is('[aria-expanded="true"]'));

          $('html, body').stop().animate({scrollTop: 0}, 500);
        }
      });

      // Close sidebar when clicked overflowed content.
      $('.main-navbar', context).click(function(e) {
        var $clickTarget = $(e.target);
        if ($clickTarget.parents('.navbar-inner').length == 0 && $clickTarget.is('.navbar-inner') == false) {
          $ ('#navbar-main button.navbar-toggler:visible', context).click();
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
      var $menuItems = $('.we-mega-menu-li.with-submenu', context);
      var $links = $('> a.we-mega-menu-li, > span.we-megamenu-nolink', $menuItems);

      if ($links.length) {
        var blockContentClass = '.we-mega-menu-submenu';

        $links.each(function() {
          var $thisLink = $(this);
          $thisLink.toggleClass('open', $thisLink.parent().is('.active'));

          // The item can be <a> tag or just a <span> if no link available - then the whole <span> is a toggler.
          var $expander = $thisLink;
          var $collapser = $thisLink.is('a') ? $thisLink.find('.d-submenu-toggler') : $thisLink;

          $expander.once().click(function () {
            var $linkItem = $(this);
            if ($linkItem.is('a.open')) {
              return true;
            }
            $linkItem.toggleClass('open').next(blockContentClass).find('> .we-mega-menu-submenu-inner').slideToggle();

            return false;
          });

          $collapser.once().click(function() {
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

  Drupal.behaviors.mainMenuDeepActiveTrail = {
    attach: function (context, settings) {
      var $menu = $('nav.navbar', context);

      if (window.location.pathname == '/') {
        return false;
      }
      $menu.find('a[href$="' + window.location.pathname + '"]').each(function() {
        var $matchingLinkTag = $(this);
        $matchingLinkTag.addClass('active-menu-item');
        // Some links are placed in basic submenus.
        $matchingLinkTag.parents('.we-mega-menu-li.with-submenu').addClass('active-trail open');
        // Some links are placed in mega menu blocks.
        $matchingLinkTag.parents('.type-of-block').addClass('active-trail open');
      });
    }
  };

})(jQuery, Drupal);
