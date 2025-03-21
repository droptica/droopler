(function ($, Drupal) {
  Drupal.behaviors.annualInformation = {
    attach: function (context, settings) {
      const items = $('.d-p-annual-information', context);
      if (!items.length) return;

      const values = [];
      items.each(function() {
        const value = parseFloat($(this).find('.d-p-annual-information__data').text().replace(/\s/g, ''));
        if (!isNaN(value)) {
          values.push(value);
        }
      });

      const maxValue = Math.max(...values);

      items.each(function() {
        const dataElement = $(this).find('.d-p-annual-information__data');
        const progressBar = $(this).find('.d-p-annual-information__bar');
        const value = parseFloat(dataElement.text().replace(/\s/g, ''));

        if (!isNaN(value)) {
          const percentage = (value / maxValue) * 100;
          progressBar.css('width', percentage + '%');

          const formattedNumber = value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
          dataElement.text(formattedNumber);
        }
      });
    }
  };
})(jQuery, Drupal);
