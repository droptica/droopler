(function ($, Drupal) {
  'use strict';

  /**
   * Changes for Single text block paragraph with custom class: with-price.
   */
  Drupal.behaviors.d_p_single_text_block = {
    attach: function (context, settings) {
      window.setPrice = function (price, change, timestamp, currency, cssClass) {
        $(document).ready(function () {
          var container = $('.wrapper-d_p_single_text_block');
          container.each(function (key, value) {
            var $this = $(this);
            var date = (new Date(timestamp * 1000));

            if ($this.hasClass(cssClass ? cssClass : 'with-price')) {
              $this.find('.with-price-value').html(price);
              $this.find('.with-price-currency').html(currency);
              $this.find('.with-price-timestamp').html(date.getHours() + ':' + date.getMinutes() + ':' + date.getSeconds());
              $this.find('.with-price-percentage').html(change + '%');
              if (change >= 0) {
                $this.find('.with-price-arrow').parents('.with-price-wrapper').removeClass('down').addClass('up');
              } else {
                $this.find('.with-price-arrow').parents('.with-price-wrapper').removeClass('up').addClass('down');
              }
            }
          });
        });
      };
    }
  };
})(jQuery, Drupal);
