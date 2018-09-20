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
      $(window).scroll(function() {
        if ($(this).scrollTop() > 50) {
          $("body").addClass("scrolled");
        }
        else{
          $("body").removeClass("scrolled");
        }
      });

      $( window ).resize(function() {
        clampTitle();
      });
      clampTitle();

      /**
       * Triger title to 2 lines and add ... .
       */
      function clampTitle() {
        var containers = document.querySelectorAll('.product-teaser-content .node__title');
        Array.prototype.forEach.call(containers, function (container) {
          var p = container.querySelector('span');
          var divh = container.clientHeight;
          while (p.offsetHeight > divh) {
            p.textContent = p.textContent.replace(/\W*\s(\S)*$/, '...');
          }
        });
      }

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
