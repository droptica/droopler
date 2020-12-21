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
      $(window).once('d_global_scrolled').scroll(function() {
        if ($(this).scrollTop() > 50) {
          $("body").addClass("scrolled");
        }
        else{
          $("body").removeClass("scrolled");
        }
      });

      $( window ).once('d_global_resize').resize(function() {
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
      $body.addClass("d-theme-preceded");
    }
  };

  /**
   * Calculates first paragraph padding for header with cta page layout.
   *
   * @type {{ attach: Drupal.behaviors.droopler_cta_header.attach }}
   */
  Drupal.behaviors.droopler_cta_header = {
    attach: function (context, settings) {

      var paragraphPaddingObserver = function ($element) {

        var paragraphPaddingApp = {
          getExtendedPaddingElement: function () {
            return $('.hanging-header');
          },
          getExtendedPaddingElementOffset: function () {
            var element = this.getExtendedPaddingElement();

            return element.offset().top + this.getExtendedPaddingElementHeight();
          },
          getExtendedPaddingElementHeight: function () {
            var $extendedElement = this.getExtendedPaddingElement();
            var extendedElementSizing = Math.ceil($extendedElement.outerHeight() + $extendedElement.position().top);

            var sizingCompare = extendedElementSizing - parseInt($extendedElement.css('padding-bottom'));

            return sizingCompare > 0 ? sizingCompare : 0;
          },
          getElementPadding: function () {
            var $clonedElement = $element.clone();

            $clonedElement
              .prop('style', '')
              .css({
                'position': 'absolute',
                'z-index': -9999,
                'top': -9999,
                'left': -9999,
              })
              .appendTo($element.parent());

            var clonedElementPadding = $clonedElement.css('padding-top');
            $clonedElement.remove();

            return parseFloat(clonedElementPadding);
          },
          isExtendedPaddingApplicable: function () {
            return $element.offset().top < 2 * this.getExtendedPaddingElementOffset();
          },
          getCombinedPaddingCssValue: function () {
            return this.getElementPadding() + this.getExtendedPaddingElementHeight() + 'px';
          },
          run: function () {
            var header = this.getExtendedPaddingElement();

            if (header.length  && $element.length) {
              if (this.isExtendedPaddingApplicable()) {
                $element.css('padding-top', this.getCombinedPaddingCssValue());
                return this;
              }
              $element.removeAttr('style');
            }

            return this;
          }
        }

        return paragraphPaddingApp.run();
      };

      var eventName = 'calculateDynamicHeaderOffset';

      $(window)
        .once(eventName)
        .bind(eventName, Drupal.debounce(function () {
          var firstParagraph = $('.paragraph-sections section:first > .paragraph:first');
          var elementToObserve = firstParagraph;

          if (firstParagraph.parent().find('.content-moved-inside .content-inside-wrapper').length) {
            elementToObserve = firstParagraph.find('.content-inside-wrapper');
          }
          else if (firstParagraph.find('.expandable-content .list-item-wrapper').length) {
            if (!firstParagraph.hasClass('d-p-group-of-text-blocks') && !firstParagraph.hasClass('d-p-carousel')) {
              firstParagraph.find('.expandable-content .list-item-wrapper .paragraph').each(function () {
                paragraphPaddingObserver($(this));
              });

              return;
            }
          }

          paragraphPaddingObserver(elementToObserve);
        }, 300));

      $(window).on('resize',function () {
        $(this).trigger(eventName);
      }).trigger(eventName);
    }
  }

  /**
   * Adds additional div above the unpublished content.
   * @type {{attach: Drupal.behaviors.droopler_unpublished.attach}}
   */
  Drupal.behaviors.droopler_unpublished = {
    attach: function (context, settings) {
      $('<div>').addClass('unpublished-message').text(Drupal.t('Unpublished')).insertBefore($('.node--unpublished', context));
    }
  };

  /**
   * Initialize tooltip.
   * @type {{attach: Drupal.behaviors.droopler_tooltip.attach}}
   */
  Drupal.behaviors.droopler_tooltip = {
    attach: function (context, settings) {
      $('[data-toggle="tooltip"]').tooltip();
    }
  };

  /**
   * Initialize popovers.
   * @type {{attach: Drupal.behaviors.droopler_popover.attach}}
   */
  Drupal.behaviors.droopler_popover = {
    attach: function (context, settings) {
      $('[data-toggle="popover"]').popover();
    }
  };

})(jQuery, Drupal);
