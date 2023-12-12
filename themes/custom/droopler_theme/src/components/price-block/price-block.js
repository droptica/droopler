(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.priceComponent = {
    attach: function (context, settings) {

      window.setPrice = function (price, change, timestamp, currency) {
        $(document).ready(function () {
          let $container = $('.price-block', context);

          $container.each(function () {
            let $this = $(this);
            let date = (new Date(timestamp * 1000));
            let positive_change = change >= 0;

            $this.find('.price-block__spinner').remove();
            $this.find('.price-block__value').html(price);
            $this.find('.price-block__currency').html(currency);
            $this.find('.price-block__timestamp').html(date.getHours() + ':' + date.getMinutes() + ':' + date.getSeconds());
            $this.find('.price-block__percentage').html(change + '%');

            $this.find('.price-block__change').removeClass(positive_change ? 'down' : 'up').addClass(positive_change ? 'up' : 'down');
          });
        });
      };

    }
  };

})(jQuery, Drupal);
