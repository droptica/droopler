/**
 * @file
 * Main menu operation.
 */
(function ($, Drupal) {

  "use strict";
  var $clearStyling = false;

  Drupal.behaviors.mainMenuMobileOperations = {
    attach: function (context, settings) {
      var $block = $('.we-mega-menu-submenu .block-block-content', context);
      var $titles = $('.field--name-field-d-main-title', $block);

      if ($titles.length) {
        var breakpointDesktop = 992;
        var blockContentClass = '.field--name-field-d-long-text';

        $titles.once().click(function() {
          if (window.innerWidth < breakpointDesktop) {
            $(this).toggleClass('open').parent().find(blockContentClass).slideToggle('medium', function () {
              if ($(this).is(':hidden')) {
                $clearStyling = true;
              }
            });

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
      $ ('#navbar-main button.navbar-toggler', context).once('d_main_menu_open').click(function() {
        if (!$('.navbar').hasClass('collapsing')) {
          $('body').toggleClass('navbar-open');
          $('.navbar').toggleClass('open');
          $('html, body').stop().animate({scrollTop: 0}, 500);
          $(this).attr('aria-expanded', ($(this).attr('aria-expanded') === 'false'));
        }
      });

      // Close sidebar when clicked overflowed content.
      $('.main-navbar', context).once('d_main_menu_close').click(function(e) {
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
        var $mainNavbar = $('.main-navbar');

        $links.each(function() {
          var $thisLink = $(this);
          $thisLink.toggleClass('open', $thisLink.parent().is('.active'));

          // The item can be <a> tag or just a <span> if no link available - then the whole <span> is a toggler.
          var $expander = $thisLink;
          var $collapser = $thisLink.is('a') ? $thisLink.find('.d-submenu-toggler') : $thisLink;

          $expander.once().click(function () {
            var $linkItem = $(this);
            if ($linkItem.is('a.open') || $mainNavbar.is(':not(.show)')) {
              return true;
            }
            $linkItem.toggleClass('open').next(blockContentClass).find('> .we-mega-menu-submenu-inner').slideToggle('medium', function () {
              if ($(this).is(':hidden')) {
                $clearStyling = true;
              }
            });

            return false;
          });

          $collapser.once().click(function() {
            if ($mainNavbar.is(':not(.show)')) {
              return true;
            }
            var $linkItem = $(this);
            if ($linkItem.is('.d-submenu-toggler')) {
              $linkItem = $linkItem.parent();
            }
            $linkItem.toggleClass('open').next(blockContentClass).find('> .we-mega-menu-submenu-inner').slideToggle('medium', function () {
              if ($(this).is(':hidden')) {
                $clearStyling = true;
              }
            });

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

  /**
   * jQuery slideUp method leaves "display: none" inline styling.
   * We need to remove it if some of menu elements was hidden and
   * then screen resolution was resized up to minimum 992px to make
   * them visible again.
   *
   * @type {{attach: Drupal.behaviors.unsetHiddenNavElements.attach}}
   */
  Drupal.behaviors.unsetHiddenNavElements = {
    attach: function (context, settings) {
      var $menu = $('nav.navbar', context);
      $(window).once('d_main_menu_resize').resize(function() {
        if (window.innerWidth > 992 && $clearStyling) {
          $menu.find('[style*="display: none"]').removeAttr('style');
          $clearStyling = false;
        }
      });
    }
  };

})(jQuery, Drupal);
