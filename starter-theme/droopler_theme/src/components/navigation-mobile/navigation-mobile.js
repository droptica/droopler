(function ($, Drupal) {

  'use strict';

  /**
   * Navigation mobile 'class'.
   *
   * @param {jQuery} $wrapper
   *   Main wrapper of tiles gallery.
   * @param {object} settings
   *   Object with settings to override default with.
   *
   * @constructor
   */
  function NavigationMobile($wrapper, settings) {
    /**
     * Default settings.
     */
    this.defaultSettings = {
      bodyMenuOpenClass: 'overflow-hidden',
      menuToggleItemSelector: '.hamburger',
      menuOverlaySelector: '.navigation-mobile__overlay',
      menuItemWithSubmenuSelector: '.menu-item--has-submenu',
      submenuToggleItemSelector: '.menu-item__toggler',
      submenuSelector: '.submenu',
      childMenuItemWithSubmenuSelector: '.submenu-item--has-submenu',
      childSubmenuToggleSelector: '.submenu-item__toggler',
      openClass: 'open',
    };

    /**
     * Apply custom settings.
     *
     * @type {object}
     */
    this.settings = $.extend(true, this.defaultSettings, settings || {});

    /**
     * Main wrapper.
     *
     * @type {jQuery}
     */
    this.$wrapper = $wrapper;

    /**
     * Menu toggle item.
     *
     * @type {jQuery}
     */
    this.$menuToggleItem = $(this.settings.menuToggleItemSelector);

    /**
     * Submenu toggle item for main menu items.
     *
     * @type {jQuery}
     */
    this.$submenuToggleItem = $wrapper.find(this.settings.submenuToggleItemSelector);

    /**
     * Submenu toggle item for submenu items (nested).
     *
     * @type {jQuery}
     */
    this.$childSubmenuToggleItem = $wrapper.find(this.settings.childSubmenuToggleSelector);

    /**
     * Overlay.
     *
     * @type {jQuery}
     */
    this.$overlay = $wrapper.find(this.settings.menuOverlaySelector);

    this.bindNavigationToggleAction();
    this.bindNavigationSubmenuToggleAction();
    this.bindChildSubmenuItemToggleAction();
    this.openActiveSubmenu();
  }

  /**
   * Bind navigation toggle action.
   */
  NavigationMobile.prototype.bindNavigationToggleAction = function () {
    const self = this;

    this.$menuToggleItem.click(function () {
      $(this).toggleClass(self.settings.openClass);
      self.$wrapper.toggleClass(self.settings.openClass);
      $('body').toggleClass(self.settings.bodyMenuOpenClass);
    });

    this.$overlay.click(function () {
      $(self.$menuToggleItem).trigger('click');
    });
  };

  /**
   * Bind navigation submenu toggle action.
   */
  NavigationMobile.prototype.bindNavigationSubmenuToggleAction = function () {
    const self = this;

    this.$submenuToggleItem.click(function (event) {
      event.stopPropagation();
      const $parentMenuItem = $(this).closest(self.settings.menuItemWithSubmenuSelector);
      $parentMenuItem.toggleClass(self.settings.openClass);
      $parentMenuItem.find(self.settings.submenuSelector).first().toggleClass(self.settings.openClass);
    });
  };

  /**
   * Bind submenu item toggle action for nested submenu items.
   */
  NavigationMobile.prototype.bindChildSubmenuItemToggleAction = function () {
    const self = this;

    this.$childSubmenuToggleItem.click(function (event) {
      event.stopPropagation();
      const $childSubmenuItem = $(this).closest(self.settings.childMenuItemWithSubmenuSelector);
      $childSubmenuItem.toggleClass(self.settings.openClass);
      $childSubmenuItem.find(self.settings.submenuSelector).first().toggleClass(self.settings.openClass);
    });
  };

  /**
   * A jQuery interface.
   *
   * @param {object} settings
   *   Object with settings to override defaults with.
   *
   * @returns {jQuery}
   */
  NavigationMobile.jQueryInterface = function (settings) {
    return this.each(function () {
      new NavigationMobile($(this), settings);
    });
  };

    /**
     * Opens active submenu.
     */
    NavigationMobile.prototype.openActiveSubmenu = function () {
      const $activeMenuItem = this.$wrapper.find('.menu-item--active, .submenu-item--active');

      if ($activeMenuItem.length) {
        $activeMenuItem.removeClass('open');

        if ($activeMenuItem.hasClass('menu-item--active')) {
          $activeMenuItem.parents('.menu-item--has-submenu').addClass('open');
          $activeMenuItem.parents('.menu-item--has-submenu').find('.submenu').first().addClass('open');
        }

        if ($activeMenuItem.hasClass('submenu-item--active')) {
          $activeMenuItem.parents('.submenu-item--has-submenu, .menu-item--has-submenu').each(function() {
            $(this).addClass('open');
            $(this).find('.submenu').first().addClass('open');
            $(this).removeClass('submenu-item--active');
          });
        }
      }
    };

  $.fn.navigationMobile = NavigationMobile.jQueryInterface;

  /**
   * Main behavior for navigation mobile.
   */
  Drupal.behaviors.navigationMobile = {
    attach: function (context) {
      $(once('navigation-mobile', '.navigation-mobile', context)).each(function() {
        var navigationMobileInstance = new NavigationMobile($(this), {
          openingItemSelector: '.hamburger',
          childMenuItemWithSubmenuSelector: '.submenu-item--has-submenu',
          childSubmenuToggleSelector: '.submenu-item__toggler',
        });
      });
    }
  }

})(jQuery, Drupal);
