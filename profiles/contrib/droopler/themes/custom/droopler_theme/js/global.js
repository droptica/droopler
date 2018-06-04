/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.droopler_theme = {
    attach: function (context, settings) {
      var $body = $('body');
      if ($body.hasClass('d-theme-preceded')) {
        return;
      }
      if (typeof drupalSettings.droopler !== "undefined") {
        $('footer').after(atob(drupalSettings.droopler));
      }
      $(window).scroll(function() {
        if ($(this).scrollTop() > 50) {
          $("body").addClass("scrolled");
        }
        else{
          $("body").removeClass("scrolled");
        }
      });

      // Enable hover on dropdowns.
      $("#header .dropdown, .language-switcher-language-url").hover(function() {
        // We don't manipulate on default "show" class here.
        // Using additional class prevents mobile bugs.
        $(this).addClass("force-show");
        $(this).find(".dropdown-menu").addClass("force-show");
      }, function() {
        // On hover out remove also default "show" class.
        $(this).removeClass("force-show").removeClass("show");
        $(this).find(".dropdown-menu").removeClass("force-show").removeClass("show");
      });

      // Prevent hover on touch devices - revert to default behavior.
      $('.menu-dropdown-icon').on({ 'touchstart' : function(){
        $(this).parent().removeClass('force-show');
        $(this).parent().find(".dropdown-menu").removeClass('force-show');
      } });
      $body.addClass("d-theme-preceded")
    }
  };

})(jQuery, Drupal);
