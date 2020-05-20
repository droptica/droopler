/**
 * @file
 * Commerce cart.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Commerce cart collapse/expand.
   *
   * @type {{attach: Drupal.behaviors.d_commerce_product_cart.attach}}
   */
  Drupal.behaviors.d_commerce_product_cart = {
    attach: function (context, settings) {

      function isNotTargetElement($element, event) {
        return !$element.is(event.target) && $element.has(event.target).length === 0;
      }

      $(document).mouseup(function (e) {
        var $cart = $('.cart-block--contents', context);
        var $cartLink = $('.cart-block--link__expand', context);

        if (
          isNotTargetElement($cart, e)
          && isNotTargetElement($cartLink, e)
          && $cart.is(':visible')
        ) {
          $cartLink.trigger('click');
        }
      });
    }
  };

})(jQuery, Drupal);
