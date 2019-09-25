(function ($) {
  'use strict';

  /**
   * Changes for Single text block paragraph with custom class: with-price.
   */
  Drupal.behaviors.d_p_single_text_block = {
    attach: function (context, settings) {
      window.setPrice = function (price, change, cssClass) {
        $(document).ready(function () {
          var container = $('.wrapper-d_p_single_text_block');
          container.each(function (key, value) {
            var $this = $(this);
            if (cssClass == null) {
              if ($this.hasClass('with-price')) {
                $this.find('.with-price-data')[0].innerHTML = price;
                $this.find('.with-price-percentage')[0].innerHTML = change + '%';
                if (change >= 0) {
                  $this.find('.with-price-arrow').removeClass('down').addClass('up');
                } else {
                  $this.find('.with-price-arrow').removeClass('up').addClass('down');
                }
              }
            }else{
              if ($this.hasClass(cssClass)) {
                $this.find('.with-price-data')[0].innerHTML = price;
                $this.find('.with-price-percentage')[0].innerHTML = change + '%';
                if (change >= 0) {
                  $this.find('.with-price-arrow').removeClass('down').addClass('up');
                } else {
                  $this.find('.with-price-arrow').removeClass('up').addClass('down');
                }
              }
            }
          });
        });
      };
    }
  };
})(jQuery);
